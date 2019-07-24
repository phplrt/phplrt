<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler;

use PhpParser\Node;
use Phplrt\Assembler\Generator\Renderer;
use Phplrt\Assembler\Generator\RendererInterface;
use Phplrt\Assembler\Generator\CodePurifierVisitor;
use Phplrt\Assembler\Generator\DependencyCleanupVisitor;

/**
 * Class Generator
 */
class Generator implements GeneratorInterface
{
    /**
     * @var string
     */
    private const DESCRIPTION_SPLIT_PATTERN = '/(.{75,}?\s+)(?=\S+)/mus';

    /**
     * @var string
     */
    private const EXPR_STRICT = 'declare(strict_types=1);';

    /**
     * @var string
     */
    private const EXPR_NAMESPACE = 'namespace %s;';

    /**
     * @var string
     */
    private const PHP_TOKEN_NAME = '[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*';

    /**
     * @var string
     */
    private const PCRE_NAMESPACE = '/^' . self::PHP_TOKEN_NAME . '(\\\\' . self::PHP_TOKEN_NAME . ')*$/u';

    /**
     * @var Node[][]
     */
    private $sources;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var array|mixed[]
     */
    private $tags = [];

    /**
     * @var bool
     */
    private $strict = false;

    /**
     * @var string|null
     */
    private $namespace;

    /**
     * @var string
     */
    private $class;

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * Generator constructor.
     *
     * @param ParserInterface $parser
     * @param string $class
     * @param iterable|Node[] $sources
     */
    public function __construct(ParserInterface $parser, string $class, iterable $sources)
    {
        $this->class = $class;
        $this->parser = $parser;
        $this->sources = $sources;
        $this->renderer = new Renderer();
    }

    /**
     * @param string ...$description
     * @return GeneratorInterface|$this
     */
    public function withDescription(string ...$description): GeneratorInterface
    {
        return $this->immutable(function () use ($description) {
            $this->description = \count($description)
                ? \implode(\PHP_EOL . \PHP_EOL, $description)
                : null;
        });
    }

    /**
     * @param \Closure $then
     * @return GeneratorInterface|static
     */
    private function immutable(\Closure $then): self
    {
        $then->call($self = clone $this);

        return $self;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return GeneratorInterface
     */
    public function withTag(string $name, $value): GeneratorInterface
    {
        return $this->immutable(function () use ($name, $value): void {
            $this->tags[] = [$name, (string)$value];
        });
    }

    /**
     * @param array|string[] $namespace
     * @return GeneratorInterface|static
     */
    public function withNamespaceChunks(array $namespace): GeneratorInterface
    {
        $argument = \count($namespace) ? \implode('\\', $namespace) : null;

        return $this->withNamespace($argument);
    }

    /**
     * @param string|null $namespace
     * @return GeneratorInterface|static
     */
    public function withNamespace(?string $namespace): GeneratorInterface
    {
        if (\is_string($namespace)) {
            $this->assertNamespace($namespace);
        }

        return $this->immutable(function () use ($namespace) {
            $this->namespace = $namespace;
        });
    }

    /**
     * @param string $namespace
     * @return void
     */
    private function assertNamespace(string $namespace): void
    {
        if (! \preg_match(self::PCRE_NAMESPACE, $namespace)) {
            $error = \sprintf('Namespace "%s" is invalid', $namespace);
            throw new \InvalidArgumentException($error);
        }
    }

    /**
     * @param bool $enabled
     * @return GeneratorInterface|static
     */
    public function strict(bool $enabled = true): GeneratorInterface
    {
        return $this->immutable(function () use ($enabled) {
            $this->strict = $enabled;
        });
    }

    /**
     * @param string|null $directory
     * @param string|null $filename
     * @return string
     * @throws \RuntimeException
     */
    public function save(string $directory = null, string $filename = null): string
    {
        $pathname = $this->getOutputPathName($filename, $directory);
        $directory = \dirname($pathname);

        if (! @\mkdir($directory, 0777, true) && ! \is_dir($directory)) {
            throw new \RuntimeException('Can not create directory ' . $directory);
        }

        $status = @\file_put_contents($pathname, $result = $this->render());

        if (\is_bool($status)) {
            throw new \RuntimeException('Can not write data into ' . $pathname);
        }

        return $result;
    }

    /**
     * @param string|null $filename
     * @param string|null $directory
     * @return string
     */
    private function getOutputPathName(?string $filename, ?string $directory): string
    {
        $filename = $filename ?? \basename(\str_replace('\\', '/', $this->class));

        return ($directory ?? (string)\getcwd()) . '/' . $filename . '.php';
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $result = ['<?php'];

        if ($this->description) {
            foreach ($this->generateDescriptionDocBlock($this->description) as $line) {
                $result[] = $line;
            }
        }

        if ($this->strict) {
            $result[] = self::EXPR_STRICT;
            $result[] = '';
        }

        if ($this->namespace) {
            $result[] = \sprintf(self::EXPR_NAMESPACE, $this->namespace);
            $result[] = '';
        }

        foreach ($this->sources as $ast) {
            $result[] = $this->renderDependency($ast);
        }

        return \implode(\PHP_EOL, $result);
    }

    /**
     * @param iterable $ast
     * @return string
     */
    private function renderDependency(iterable $ast): string
    {
        return $this->renderer->render(
            $this->parser->modify($ast, [
                new DependencyCleanupVisitor(),
                new CodePurifierVisitor()
            ])
        );
    }

    /**
     * @param string $description
     * @return \Traversable|string[]
     */
    private function generateDescriptionDocBlock(string $description): \Traversable
    {
        yield '/**';

        foreach ($this->generateDescription($description) as $line) {
            yield ' * ' . $line;
        }

        if (\count($this->tags)) {
            yield ' *';

            foreach ($this->tags as [$name, $value]) {
                yield ' * @' . $name . ' ' . $value;
            }
        }

        yield ' */';
    }

    /**
     * @param string $description
     * @return \Traversable|string[]
     */
    private function generateDescription(string $description): \Traversable
    {
        foreach (\explode("\n", $description) as $line) {
            yield from $this->explodeLine($line);
        }
    }

    /**
     * @param string $line
     * @return iterable|string[]
     */
    private function explodeLine(string $line): iterable
    {
        $wrapped = \preg_replace(self::DESCRIPTION_SPLIT_PATTERN, "$1\n", $line);

        return \explode("\n", $wrapped);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
