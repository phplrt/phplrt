<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Builder\Analyzer;
use Phplrt\Compiler\Builder\IdCollection;
use Phplrt\Compiler\Builder\IncludesExecutor;
use Phplrt\Compiler\Exception\GrammarException;
use Phplrt\Compiler\Grammar\GrammarInterface;
use Phplrt\Compiler\Grammar\PP2Grammar;
use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Lexer\Lexer;
use Phplrt\Parser\Parser;
use Phplrt\Source\File;
use Phplrt\Source\ReadableInterface;
use Phplrt\Visitor\Traverser;
use Phplrt\Visitor\TraverserInterface;

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
    private $builder;

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
        $this->builder   = new Analyzer($ids);
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
            return $this->preloader->traverse(
                $this->grammar->parse($source)
            );
        } catch (GrammarException $e) {
            throw $e;
        }
    }

    /**
     * @param resource|string|ReadableInterface $source
     * @return iterable
     * @throws ParserExceptionInterface
     * @throws RuntimeExceptionInterface
     * @throws \Throwable
     */
    public function parse($source): iterable
    {
        $lexer = new Lexer($this->builder->tokens, $this->builder->skip);

        $parser = new Parser($lexer, $this->builder->rules, $this->builder->initial);

        return $parser->parse($source);
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
            ->with($this->builder)
            ->traverse($ast);

        return $this;
    }
}
