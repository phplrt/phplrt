<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\StackTrace\Trace;
use Phplrt\Visitor\Traverser;
use Phplrt\Visitor\VisitorInterface;
use Phplrt\Compiler\Grammar\PP2Grammar;
use Phplrt\StackTrace\VisitorDecorator;
use Phplrt\StackTrace\Record\NodeRecord;
use Phplrt\Compiler\Builder\LexerBuilder;
use Phplrt\StackTrace\Record\TokenRecord;
use Phplrt\Compiler\Builder\ParserBuilder;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Compiler\Builder\IncludesExecutor;
use Phplrt\Compiler\Grammar\GrammarInterface;
use Phplrt\StackTrace\TraceableNodeInterface;
use Phplrt\Compiler\Exception\GrammarException;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;

/**
 * Class Compiler
 */
class Compiler
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
     * @var Trace
     */
    private $trace;

    /**
     * Compiler constructor.
     *
     * @param GrammarInterface|null $grammar
     */
    public function __construct(GrammarInterface $grammar = null)
    {
        $this->grammar = $grammar ?? new PP2Grammar();

        $this->trace = new Trace();
        $this->lexer = new LexerBuilder();
        $this->parser = new ParserBuilder();
    }

    /**
     * @param string $pathname
     * @return Compiler|$this
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function load(string $pathname): self
    {
        $this->trace = new Trace();

        $this->run($pathname);

        return $this;
    }

    /**
     * @param string $pathname
     * @return iterable
     * @throws \ReflectionException
     * @throws \Throwable
     */
    private function run(string $pathname): iterable
    {
        try {
            return $this->parse($pathname);
        } catch (RuntimeExceptionInterface $e) {
            $this->trace->push(new TokenRecord($pathname, $e->getToken()));

            throw $this->error($e);
        } catch (ParserRuntimeException $e) {
            $node = $e->getNode();
            $filename = $node instanceof TraceableNodeInterface
                ? $node->getFile()
                : $pathname;

            $this->trace->push(new NodeRecord($filename, $e->getNode()));

            throw $this->error($e);
        } catch (\Throwable $e) {
            throw $this->error($e);
        }
    }

    /**
     * @param string $pathname
     * @return iterable
     * @throws \Throwable
     */
    private function parse(string $pathname): iterable
    {
        $ast = $this->grammar->parse(\fopen($pathname, 'rb+'));

        $executor = function (string $pathname): iterable {
            return $this->parse($pathname);
        };

        return (new Traverser())
            ->with($this->decorate(new IncludesExecutor($pathname, $executor)))
            ->with($this->decorate($this->lexer))
            ->with($this->decorate($this->parser))
            ->traverse($ast);
    }

    /**
     * @param VisitorInterface $visitor
     * @return VisitorInterface
     */
    private function decorate(VisitorInterface $visitor): VisitorInterface
    {
        return new VisitorDecorator($this->trace, $visitor);
    }

    /**
     * @param \Throwable $e
     * @return GrammarException|\Throwable
     * @throws \ReflectionException
     */
    private function error(\Throwable $e): GrammarException
    {
        $exception = new GrammarException($e->getMessage(), $e->getCode(), $e);
        $exception->trace = $this->trace;

        return $this->trace->patch($exception, true);
    }

    /**
     * @return ParserInterface
     */
    public function execute(): ParserInterface
    {
        return $this->parser->getParser($this->lexer->getLexer());
    }
}
