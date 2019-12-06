<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Lexer\Lexer;
use Phplrt\Source\File;
use Phplrt\Parser\Parser;
use Phplrt\Lexer\Multistate;
use Phplrt\Visitor\Traverser;
use Phplrt\Visitor\TraverserInterface;
use Phplrt\Compiler\Grammar\PP2Grammar;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Compiler\Generator\ZendGenerator;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Compiler\Grammar\GrammarInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Compiler\Exception\GrammarException;
use Phplrt\Compiler\Generator\GeneratorInterface;
use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Parser\Exception\ParserRuntimeExceptionInterface;

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
     * @var Analyzer
     */
    private $analyzer;

    /**
     * @var Traverser
     */
    private $preloader;

    /**
     * Compiler constructor.
     *
     * @param GrammarInterface|null $grammar
     */
    public function __construct(GrammarInterface $grammar = null)
    {
        $this->grammar = $grammar ?? new PP2Grammar();

        $this->preloader = $this->bootPreloader($ids = new IdCollection());
        $this->analyzer  = new Analyzer($ids);
    }

    /**
     * @param IdCollection $ids
     * @return TraverserInterface
     */
    private function bootPreloader(IdCollection $ids): TraverserInterface
    {
        return (new Traverser())
            ->with(new IncludesExecutor(function (string $pathname): iterable {
                return $this->run(File::fromPathname($pathname));
            }))
            ->with($ids);
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

            return $this->preloader->traverse($ast);
        } catch (GrammarException $e) {
            throw $e;
        } catch (LexerRuntimeExceptionInterface $e) {
            throw new GrammarException($e->getMessage(), $source, $e->getToken()->getOffset());
        } catch (ParserRuntimeExceptionInterface $e) {
            throw new GrammarException($e->getMessage(), $source, $e->getNode()->getOffset());
        }
    }

    /**
     * @param resource|string|ReadableInterface $source
     * @return iterable
     * @throws ParserExceptionInterface
     * @throws \Throwable
     */
    public function parse($source): iterable
    {
        $lexer = $this->createLexer();

        $parser = new Parser($lexer, $this->analyzer->rules, [
            Parser::CONFIG_INITIAL_RULE => $this->analyzer->initial,
        ]);

        return $parser->parse($source);
    }

    /**
     * @return LexerInterface
     */
    private function createLexer(): LexerInterface
    {
        if (\count($this->analyzer->tokens) === 1) {
            return new Lexer($this->analyzer->tokens[Analyzer::STATE_DEFAULT], $this->analyzer->skip);
        }

        $states = [];

        foreach ($this->analyzer->tokens as $state => $tokens) {
            $states[$state] = new Lexer($tokens, $this->analyzer->skip);
        }

        return new Multistate($states, $this->analyzer->transitions);
    }

    /**
     * @param string|resource|ReadableInterface $source
     * @return Compiler|$this
     * @throws \Throwable
     */
    public function load($source): self
    {
        $ast = $this->run(File::new($source));

        (new Traverser())
            ->with($this->analyzer)
            ->traverse($ast);

        return $this;
    }

    /**
     * @return Analyzer
     */
    public function getAnalyzer(): Analyzer
    {
        return $this->analyzer;
    }

    /**
     * @return GeneratorInterface|ZendGenerator
     */
    public function build(): GeneratorInterface
    {
        return new ZendGenerator($this->analyzer);
    }
}
