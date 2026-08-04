<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Analysis\Diagnostic;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\FailureResult;
use Phplrt\Parser\Analysis\Result\PartialResult;
use Phplrt\Parser\Analysis\Result\Result;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Parser\Exception\ParserSourceException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\ArrayBuffer;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\RecursiveDescentTracer;
use Phplrt\Parser\Internal\Reduction\ReducerTable;
use Phplrt\Parser\Internal\Tracing\GrammarTable;
use Phplrt\Parser\Internal\Tracing\Result\Failure;
use Phplrt\Parser\Internal\Tracing\Result\Success;

/**
 * @phpstan-import-type ReducerType from ReducerTable
 */
readonly class Parser implements ParserInterface
{
    private GrammarTable $table;

    private ReducerTable $reducers;

    /**
     * @param list<RuleInterface> $grammar
     * @param int<0, max> $initial the identifier of the rule the analysis
     *        starts at
     * @param array<int<0, max>, ReducerType> $reducers
     * @param array<int, array<int, true>> $startTokens
     * @param array<int, bool> $matchesEmptyInput
     * @param array<int, bool> $presentInTree
     */
    public function __construct(
        private LexerInterface $lexer,
        array $grammar,
        int $initial,
        array $reducers = [],
        array $startTokens = [],
        array $matchesEmptyInput = [],
        array $presentInTree = [],
    ) {
        $this->table = new GrammarTable(
            rules: $grammar,
            initial: $initial,
            startTokens: $startTokens,
            matchesEmptyInput: $matchesEmptyInput,
            presentInTree: $presentInTree,
        );

        $this->reducers = new ReducerTable(
            grammar: $grammar,
            reducers: $reducers,
            rule: $initial,
        );
    }

    private function trace(BufferInterface $buffer): Success|Failure
    {
        return RecursiveDescentTracer::trace($this->table, $buffer);
    }

    /**
     * Describes what has kept the grammar from reading the source any further.
     *
     * @param TokenInterface $stoppedAt the token to describe in case of the
     *        grammar has broken nowhere in particular
     */
    private function describe(
        ReadableInterface $source,
        Failure $failure,
        TokenInterface $stoppedAt,
    ): UnexpectedTokenException {
        // A grammar reads much further than it keeps, so what is wrong is
        // where the reading has broken rather than where it has stopped: a
        // source ending in the middle of a rule stops where that rule begins,
        // and breaks at the end of the input
        $token = $failure->token ?? $stoppedAt;

        return UnexpectedTokenException::fromToken($source, $token, $failure->expected);
    }

    /**
     * Reads as much of the source as the grammar describes and reports what it
     * has made of it.
     *
     * Nothing about the source is an error: how far the grammar goes is told
     * by the class of the result, and what stands in the way by its
     * diagnostics.
     *
     * @return ($mode is Mode::Tolerant ? Result : Result<null>)
     * @throws ParserSourceException in case of the source cannot be read
     * @throws LexerExceptionInterface in case of the source cannot be read into
     *         tokens
     * @throws LexerRuntimeExceptionInterface in case of the source contains
     *         what no token recognizes
     */
    public function analyze(ReadableInterface $source, Mode $mode = Mode::Tolerant): Result
    {
        $buffer = $this->lex($source);
        $result = $this->trace($buffer);

        if ($result instanceof Failure) {
            $error = $this->describe($source, $result, $buffer->current);

            return new FailureResult($error->token, [new Diagnostic($error)]);
        }

        $value = $mode === Mode::Tolerant ? $this->reduce($source, $result) : null;

        if ($result->furthest === null) {
            return new SuccessfulResult($value);
        }

        return new PartialResult($value, $result->stoppedAt, [
            new Diagnostic($this->describe($source, $result->furthest, $result->stoppedAt)),
        ]);
    }

    /**
     * Parses the source into an AST.
     *
     * A source the grammar does not describe in full is an error rather than
     * a result.
     *
     * @throws UnexpectedTokenException on a syntax error
     * @throws ParserSourceException in case of the source cannot be read
     * @throws LexerExceptionInterface in case of the source cannot be read into
     *         tokens
     * @throws LexerRuntimeExceptionInterface in case of the source contains
     *         what no token recognizes
     */
    public function parse(ReadableInterface $source): mixed
    {
        $buffer = $this->lex($source);
        $result = $this->trace($buffer);

        if ($result instanceof Failure) {
            throw $this->describe($source, $result, $buffer->current);
        }

        if ($result->furthest !== null) {
            throw $this->describe($source, $result->furthest, $result->stoppedAt);
        }

        return $this->reduce($source, $result);
    }

    /**
     * @throws ParserSourceException in case of the source cannot be read
     */
    private function reduce(ReadableInterface $source, Success $result): mixed
    {
        try {
            $content = $source->content;
        } catch (SourceExceptionInterface $e) {
            throw ParserSourceException::becauseSourceIsNotReadable($e);
        }

        return $this->reducers->createReducer($source, $content)
            ->reduce($result);
    }

    /**
     * Which tokens reach the grammar is decided by the lexer: a token it does
     * not report is a token the grammar is not written in terms of.
     *
     * @throws LexerExceptionInterface in case of the source cannot be read into
     *         tokens
     * @throws LexerRuntimeExceptionInterface in case of the source contains
     *         what no token recognizes
     */
    private function lex(ReadableInterface $source): BufferInterface
    {
        return new ArrayBuffer($this->lexer->lex($source));
    }
}
