<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\FailureResult;
use Phplrt\Parser\Analysis\Result\PartialResult;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Exception\ParserSourceException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\ArrayBuffer;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\Reduction\ReducerTable;
use Phplrt\Parser\Internal\Tracing\GrammarTable;
use Phplrt\Parser\Internal\Tracing\RecursiveDescentTracer;
use Phplrt\Parser\Internal\Tracing\Result\FailureTracingResult;
use Phplrt\Parser\Internal\Tracing\Result\SuccessfulTracingResult;
use Phplrt\Parser\Internal\Tracing\Result\TracingResult;

/**
 * @template TResult of mixed = mixed
 *
 * @template-implements ParserInterface<TResult>
 *
 * @phpstan-type ExpectationsTableType array<int, non-empty-string>
 * @phpstan-import-type ReducerType from ReducerTable
 * @phpstan-import-type LookaheadTableType from GrammarTable
 * @phpstan-import-type KeptTableType from GrammarTable
 * @phpstan-import-type ChoicePredictionTableType from GrammarTable
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
     * @param LookaheadTableType $lookahead the tokens a rule may begin with,
     *        or {@see null} for a rule that may begin with any of them
     * @param KeptTableType $kept The rule identifiers that become a node
     *        of the result
     * @param ChoicePredictionTableType $choicePrediction the alternatives
     *        of every alternation worth trying, indexed by the token the
     *        reading is at
     * @param ExpectationsTableType $expectations the way an error has to
     *        name each token: by its name, or by what an anonymous one is
     *        recognized by
     */
    public function __construct(
        private LexerInterface $lexer,
        array $grammar,
        int $initial,
        array $reducers = [],
        array $lookahead = [],
        array $kept = [],
        array $choicePrediction = [],
        private array $expectations = [],
    ) {
        $this->table = new GrammarTable(
            rules: $grammar,
            initial: $initial,
            lookahead: $lookahead,
            kept: $kept,
            choicePrediction: $choicePrediction,
        );

        $this->reducers = new ReducerTable(
            grammar: $grammar,
            reducers: $reducers,
            rule: $initial,
        );
    }

    /**
     * Reads as much of the source as the grammar describes and reports what it
     * has made of it.
     *
     * Nothing about the source is an error: how far the grammar goes is told
     * by the class of the result, and what stands in the way by the error it
     * carries.
     *
     * @return ($mode is Mode::Tolerant
     *      ? (SuccessfulResult<TResult>|FailureResult)
     *      : (SuccessfulResult<null>|FailureResult))
     * @throws ParserSourceException in case of the source cannot be read
     * @throws LexerExceptionInterface in case of the source cannot be read into
     *         tokens
     * @throws LexerRuntimeExceptionInterface in case of the source contains
     *         what no token recognizes
     */
    public function analyze(ReadableInterface $source, Mode $mode = Mode::Tolerant): SuccessfulResult|FailureResult
    {
        $result = $this->trace($source);

        if (!$result instanceof FailureTracingResult) {
            /** @var SuccessfulResult<TResult>|SuccessfulResult<null> */
            return new SuccessfulResult(
                value: $this->reduce($source, $result, $mode),
            );
        }

        $error = $this->createException($source, $result);

        // A grammar that has read nothing has built nothing either, so there is
        // no fragment to report and the source is only described by the error
        if ($result->length === 0) {
            return new FailureResult(
                token: $error->token,
                error: $error,
            );
        }

        /** @var PartialResult<TResult>|PartialResult<null> */
        return new PartialResult(
            value: $this->reduce($source, $result, $mode),
            token: $result->stoppedAt,
            error: $error,
        );
    }

    public function parse(ReadableInterface $source): mixed
    {
        $result = $this->trace($source);

        if ($result instanceof FailureTracingResult) {
            throw $this->createException($source, $result);
        }

        return $this->reduce($source, $result, Mode::Tolerant);
    }

    /**
     * @return ($mode is Mode::SyntaxCheck ? null : TResult)
     * @throws ParserSourceException in case of the source cannot be read
     */
    private function reduce(ReadableInterface $source, TracingResult $result, Mode $mode): mixed
    {
        if ($mode === Mode::SyntaxCheck) {
            return null;
        }

        try {
            $content = $source->content;
        } catch (SourceExceptionInterface $e) {
            throw ParserSourceException::becauseSourceIsNotReadable($e);
        }

        return $this->reducers->createReducer($source, $content)
            ->reduce($result);
    }

    private function createException(ReadableInterface $source, FailureTracingResult $result): ParserRuntimeException
    {
        $expected = [];

        foreach ($result->expected as $tokenId) {
            $expectation = $this->expectations[$tokenId] ?? null;

            if ($expectation !== null) {
                $expected[] = $expectation;
            }
        }

        return UnexpectedTokenException::becauseUnexpectedTokenProduced(
            source: $source,
            token: $result->token ?? $result->stoppedAt,
            expected: $expected,
        );
    }

    private function trace(ReadableInterface $source): SuccessfulTracingResult|FailureTracingResult
    {
        $buffer = $this->lex($source);

        return RecursiveDescentTracer::trace($this->table, $buffer);
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
        // TODO Add lexer's try/catch
        return new ArrayBuffer($this->lexer->lex($source));
    }
}
