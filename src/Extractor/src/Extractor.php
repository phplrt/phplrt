<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Extractor;

use PhpParser\Parser;
use Phplrt\Source\File;
use PhpParser\Node\Stmt;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\PrettyPrinterAbstract;
use PhpParser\PrettyPrinter\Standard;
use Phplrt\Extractor\Visitor\AliasVisitor;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Extractor\Visitor\DependenciesVisitor;

/**
 * Class Extractor
 */
class Extractor implements ExtractorInterface
{
    /**
     * @var Parser
     */
    private Parser $parser;

    /**
     * @var PrettyPrinterAbstract
     */
    private PrettyPrinterAbstract $printer;

    /**
     * @var array
     */
    private array $replaces = [];

    /**
     * Extractor constructor.
     */
    public function __construct()
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard();
    }

    /**
     * {@inheritDoc}
     */
    public function import(string $fqn, string $alias): void
    {
        $this->replaces[$this->normalize($fqn)] = $this->fqn($alias);
    }

    /**
     * @param string $fqn
     * @return string
     */
    private function normalize(string $fqn): string
    {
        return \trim($fqn, '\\');
    }

    /**
     * @param string $fqn
     * @return string
     */
    private function fqn(string $fqn): string
    {
        return '\\' . $this->normalize($fqn);
    }

    /**
     * {@inheritDoc}
     * @throws \ReflectionException
     */
    public function extract(string $fqn, string $as = null): string
    {
        $source = File::fromPathname((new \ReflectionClass($fqn))->getFileName());

        [$fqn, $ast] = $this->read($source, $fqn);

        return $this->execute($ast, $fqn, $as);
    }

    /**
     * @param ClassLike $stmt
     * @param string $class
     * @param string|null $alias
     * @return string
     */
    private function execute(ClassLike $stmt, string $class, string $alias = null): string
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new AliasVisitor($this->getReplaces($this->fqn($class), $alias)));
        $traverser->traverse([$stmt]);

        return $this->print([$stmt]);
    }

    /**
     * @param string $class
     * @param string $alias
     * @return array
     */
    private function getReplaces(string $class, string $alias = null): array
    {
        $result = [];

        $class = $this->normalize($class);
        $alias = $this->fqn($alias ?? $this->replaces[$class] ?? $class);

        $replaces = \array_merge($this->replaces, [
            $this->normalize($class) => $this->basename($alias)
        ]);

        foreach ($replaces as $from => $to) {
            if ($this->normalize($from) === $class) {
                $to = $this->basename($alias ?? $to);
            }

            $result[$from] = $to;
        }

        return $result;
    }

    /**
     * @param string $class
     * @return string
     */
    private function basename(string $class): string
    {
        $chunks = \explode('\\', $class);

        return \array_pop($chunks);
    }

    /**
     * @param array|Stmt[] $statements
     * @return string
     */
    private function print(array $statements): string
    {
        return $this->printer->prettyPrint($statements);
    }

    /**
     * @param ReadableInterface $source
     * @param string|null $fqn
     * @return array
     */
    private function read(ReadableInterface $source, string $fqn = null): array
    {
        $needle = $fqn ? $this->fqn($fqn) : null;

        $traverser = new NodeTraverser();
        $traverser->addVisitor($dependencies = new DependenciesVisitor());
        $traverser->traverse($this->parser->parse($source->getContents()));

        foreach ($dependencies->getClasses() as $name => $ast) {
            $haystack = $this->fqn($name);

            if ($needle === null || $haystack === $needle) {
                return [$haystack, $ast];
            }
        }

        throw new NotFoundException('Can not extract class, interface or trait from sources');
    }

    /**
     * {@inheritDoc}
     */
    public function extractSource($source, string $as = null): string
    {
        [$class, $ast] = $this->read(File::new('<?php ' . $source));

        return $this->execute($ast, $class, $as);
    }
}
