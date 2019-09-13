<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Source\File;
use Phplrt\Visitor\Traverser;
use Phplrt\Source\ReadableInterface;
use Phplrt\Compiler\Grammar\PP2Grammar;
use Phplrt\Compiler\Builder\LexerBuilder;
use Phplrt\Compiler\Builder\ParserBuilder;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Compiler\Builder\IncludesExecutor;
use Phplrt\Compiler\Grammar\GrammarInterface;
use Phplrt\Compiler\Exception\GrammarException;
use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

/**
 * Class Compiler
 */
class Compiler implements ParserInterface
{
    /**
     * @var GrammarInterface
     */
    private $grammar;

    /**
     * @var LexerBuilder
     */
    private $lexer;

    /**
     * @var ParserBuilder
     */
    private $parser;

    /**
     * @var Traverser
     */
    private $traverser;

    /**
     * Compiler constructor.
     *
     * @param GrammarInterface|null $grammar
     */
    public function __construct(GrammarInterface $grammar = null)
    {
        $this->grammar = $grammar ?? new PP2Grammar();

        $this->lexer = new LexerBuilder();
        $this->parser = new ParserBuilder();

        $this->traverser = (new Traverser())
            ->with(new IncludesExecutor(function (string $pathname): iterable {
                return $this->run(File::fromPathname($pathname));
            }))
            ->with($this->lexer)
            ->with($this->parser);
    }

    /**
     * @param ReadableInterface $source
     * @return iterable
     * @throws \Throwable
     */
    private function run(ReadableInterface $source): iterable
    {
        try {
            $ast = $this->grammar->parse($source);

            return $this->traverser->traverse($ast);
        } catch (GrammarException $e) {
            throw $e;
        }
    }

    /**
     * @param resource|string|ReadableInterface $source
     * @return iterable
     * @throws ParserExceptionInterface
     * @throws RuntimeExceptionInterface
     */
    public function parse($source): iterable
    {
        $parser = $this->parser->getParser($this->lexer->getLexer());

        return $parser->parse($source);
    }

    /**
     * @param string|resource|ReadableInterface $source
     * @return Compiler|$this
     * @throws \Throwable
     */
    public function load($source): self
    {
        $this->run(File::new($source));

        return $this;
    }
}
