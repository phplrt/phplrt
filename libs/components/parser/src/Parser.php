<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\FailureResult;
use Phplrt\Parser\Analysis\Result\PartialResult;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Exception\UnknownInitialRuleException;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Engine\Building;
use Phplrt\Parser\Internal\Engine\EngineInterface;
use Phplrt\Parser\Internal\Engine\Failure;
use Phplrt\Parser\Internal\Engine\Result\FailureEngineResult;
use Phplrt\Parser\Internal\Engine\Result\SuccessfulEngineResult;
use Phplrt\Parser\Internal\Engine\Tracing\TracingEngine;
use Phplrt\Parser\Internal\MessageInterpolator;
use Phplrt\Parser\Internal\Engine\Tracing\Reduction\ReducerTable;
use Phplrt\Parser\Internal\Engine\Tracing\GrammarTable;

/**
 * @template TResult of mixed = mixed
 *
 * @template-implements ParserInterface<TResult>
 *
 * @phpstan-type ExpectationsTableType array<int, non-empty-string>
 *
 * @phpstan-import-type ReducerType from ReducerTable
 * @phpstan-import-type LookaheadTableType from GrammarTable
 * @phpstan-import-type KeptTableType from GrammarTable
 * @phpstan-import-type ChoicePredictionTableType from GrammarTable
 * @phpstan-import-type MessageTableType from GrammarTable
 *
 * @readonly
 */
class Parser implements ParserInterface
{
    /**
     * Reads the sources on behalf of the parser.
     *
     * Every engine reads the same sources into the same values, so the one
     * chosen is the one that is cheaper for the sources the parser is given,
     * and nothing but the parser itself knows which one it is.
     */
    private readonly EngineInterface $tracing;

    /**
     * @param array<int<0, max>, ReducerType> $reducers
     * @param LookaheadTableType $lookahead the tokens a rule may begin with,
     *        or {@see null} for a rule that may begin with any of them
     * @param KeptTableType $kept The rule identifiers that become a node
     *        of the result
     * @param ChoicePredictionTableType $choicePrediction the alternatives
     *        of every alternation worth trying, indexed by the token the
     *        reading is at
     */
    public function __construct(
        LexerInterface $lexer,
        /**
         * The rules the analysis may start at.
         *
         * @var list<RuleInterface>
         */
        private readonly array $grammar,
        /**
         * The identifier of the rule the analysis starts at.
         *
         * @var int<0, max>
         */
        private int $initial,
        array $reducers = [],
        array $lookahead = [],
        array $kept = [],
        array $choicePrediction = [],
        /**
         * The way an error has to name each token: by its name, or by what an
         * anonymous one is recognized by
         *
         * @var ExpectationsTableType
         */
        private readonly array $expectations = [],
        /**
         * The message describing the failure of a rule, indexed by the
         * rule identifiers
         *
         * @var MessageTableType
         */
        private readonly array $messages = [],
    ) {
        $this->tracing = new TracingEngine(
            lexer: $lexer,
            grammar: $grammar,
            reducers: $reducers,
            lookahead: $lookahead,
            kept: $kept,
            choicePrediction: $choicePrediction,
            messages: $messages,
        );
    }

    /**
     * Returns the parser starting the analysis at the given rule.
     *
     * @api
     *
     * @param int<0, max> $rule
     * @throws UnknownInitialRuleException in case of the analysis may not be
     *         started at the given rule
     */
    public function withInitial(int $rule): static
    {
        if (!isset($this->grammar[$rule])) {
            throw UnknownInitialRuleException::becauseRuleIsNotDefined($rule);
        }

        $self = clone $this;
        $self->initial = $rule;   // @phpstan-ignore property.readOnlyByPhpDocAssignNotInConstructor

        return $self;
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
     * @throws LexerExceptionInterface in case of the source cannot be read into
     *         tokens
     * @throws LexerRuntimeExceptionInterface in case of the source contains
     *         what no token recognizes
     */
    public function analyze(ReadableInterface $source, Mode $mode = Mode::Tolerant): SuccessfulResult|FailureResult
    {
        $result = $this->tracing->read($source, $this->initial, match ($mode) {
            Mode::SyntaxCheck => Building::Nothing,
            Mode::Tolerant => Building::Anything,
        });

        if ($result instanceof SuccessfulEngineResult) {
            /** @var SuccessfulResult<TResult>|SuccessfulResult<null> */
            return new SuccessfulResult(
                value: $result->value,
            );
        }

        $error = $this->createException($source, $result->failure);

        if ($result instanceof FailureEngineResult) {
            return new FailureResult(
                token: $error->token,
                error: $error,
            );
        }

        /** @var PartialResult<TResult>|PartialResult<null> */
        return new PartialResult(
            value: $result->value,
            token: $result->stoppedAt,
            error: $error,
        );
    }

    public function parse(ReadableInterface $source): mixed
    {
        $result = $this->tracing->read($source, $this->initial, Building::Whole);

        if (!$result instanceof SuccessfulEngineResult) {
            throw $this->createException($source, $result->failure);
        }

        return $result->value;
    }

    private function createException(ReadableInterface $source, Failure $failure): ParserRuntimeException
    {
        $expected = [];

        foreach ($failure->expected as $tokenId) {
            $expectation = $this->expectations[$tokenId] ?? null;

            if ($expectation !== null) {
                $expected[] = $expectation;
            }
        }

        $token = $failure->token;
        $rule = $failure->labelled;
        $message = $rule === null ? null : $this->messages[$rule] ?? null;

        // The grammar says nothing about this failure, so it is described by
        // the tokens that could have been read instead
        if ($rule === null || $message === null) {
            return UnexpectedTokenException::becauseUnexpectedTokenProduced(
                source: $source,
                token: $token,
                expected: $expected,
            );
        }

        return UnexpectedTokenException::becauseGrammarDescribesTheError(
            source: $source,
            token: $token,
            message: (new MessageInterpolator())
                ->interpolate($message, $source, $token, $expected),
            expected: $expected,
            rule: $rule,
        );
    }
}
