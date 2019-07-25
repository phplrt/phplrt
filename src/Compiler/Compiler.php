<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Grammar\Reader;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\Readable;
use Phplrt\Exception\ExternalException;
use Phplrt\Source\Exception\NotReadableException;
use Zend\Code\Exception\InvalidArgumentException;
use Zend\Code\Generator\Exception\RuntimeException;
use Zend\Code\Generator\ValueGenerator as Value;

/**
 * Class Compiler
 */
class Compiler implements ParserInterface
{
    /**
     * @var string|null
     */
    private $namespace;

    /**
     * @var string
     */
    private $class = 'Parser';

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * Compiler constructor.
     *
     * @param ParserInterface $parser
     */
    public function __construct(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments = [])
    {
        return $this->parser->$name(...$arguments);
    }

    /**
     * @param Readable $input
     * @return mixed|NodeInterface
     */
    public function parse(Readable $input)
    {
        return $this->parser->parse($input);
    }

    /**
     * @param Readable $grammar
     * @return Compiler
     * @throws ExternalException
     * @throws NotReadableException
     */
    public static function load(Readable $grammar): self
    {
        $reader = new Reader($grammar);

        return new static($reader->getParser());
    }

    /**
     * @param ParserInterface $parser
     * @return Compiler
     */
    public static function fromParser(ParserInterface $parser): self
    {
        return new static($parser);
    }

    /**
     * @param string $namespace
     * @return Compiler
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @param string $name
     * @return Compiler
     */
    public function setClassName(string $name): self
    {
        $this->class = $name;

        return $this;
    }

    /**
     * @param string $path
     * @throws \Throwable
     */
    public function saveTo(string $path): void
    {
        $pathName = $path . '/' . $this->class . '.php';

        if (\is_file($pathName)) {
            \unlink($pathName);
        }

        \file_put_contents($pathName, $this->build());
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function build(): string
    {
        \ob_start();

        try {
            require __DIR__ . '/Resources/templates/parser.tpl.php';

            return \ob_get_contents();
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            \ob_end_clean();
        }
    }

    /**
     * @param mixed $value
     * @return string
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function render($value): string
    {
        $generator = new Value($value, Value::TYPE_AUTO, Value::OUTPUT_SINGLE_LINE);

        return $generator->generate();
    }
}
