<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Engine\Tracing;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\ArrayBuffer;
use Phplrt\Parser\Internal\Engine\Building;
use Phplrt\Parser\Internal\Engine\EngineInterface;
use Phplrt\Parser\Internal\Engine\Failure;
use Phplrt\Parser\Internal\Engine\Result\EngineResult;
use Phplrt\Parser\Internal\Engine\Result\FailureEngineResult;
use Phplrt\Parser\Internal\Engine\Result\PartialEngineResult;
use Phplrt\Parser\Internal\Engine\Result\SuccessfulEngineResult;
use Phplrt\Parser\Internal\Engine\Tracing\Reduction\ReducerTable;
use Phplrt\Parser\Internal\Engine\Tracing\Result\FailureTracingResult;
use Phplrt\Parser\Internal\Engine\Tracing\Result\TracingResult;

/**
 * Recognizes the source first and builds the values afterwards.
 *
 * The reading leaves a trace of the rules it has gone through, and the trace
 * is reduced once the reading is over, so nothing is ever built for a rule
 * the grammar gives up on. The whole source is read into tokens before the
 * grammar looks at it.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 *
 * @phpstan-import-type ReducerType from ReducerTable
 * @phpstan-import-type LookaheadTableType from GrammarTable
 * @phpstan-import-type KeptTableType from GrammarTable
 * @phpstan-import-type ChoicePredictionTableType from GrammarTable
 * @phpstan-import-type MessageTableType from GrammarTable
 *
 * @readonly
 */
final class TracingEngine implements EngineInterface
{
    private readonly GrammarTable $table;

    private readonly ReducerTable $reducers;

    /**
     * @param list<RuleInterface> $grammar
     * @param array<int<0, max>, ReducerType> $reducers
     * @param LookaheadTableType $lookahead the tokens a rule may begin with,
     *        or {@see null} for a rule that may begin with any of them
     * @param KeptTableType $kept the rule identifiers that become a node
     *        of the result
     * @param ChoicePredictionTableType $choicePrediction the alternatives
     *        of every alternation worth trying, indexed by the token the
     *        reading is at
     * @param MessageTableType $messages the message describing the failure
     *        of a rule, indexed by the rule identifiers
     */
    public function __construct(
        private readonly LexerInterface $lexer,
        array $grammar,
        array $reducers = [],
        array $lookahead = [],
        array $kept = [],
        array $choicePrediction = [],
        array $messages = [],
    ) {
        $this->table = new GrammarTable(
            rules: $grammar,
            lookahead: $lookahead,
            kept: $kept,
            choicePrediction: $choicePrediction,
            messages: $messages,
        );

        $this->reducers = new ReducerTable(
            grammar: $grammar,
            reducers: $reducers,
        );
    }

    /**
     * @param int<0, max> $initial
     */
    public function read(
        ReadableInterface $source,
        int $initial,
        Building $building,
    ): EngineResult {
        // TODO Add lexer's try/catch
        $buffer = new ArrayBuffer($this->lexer->lex($source));

        $result = RecursiveDescentTracer::trace($this->table, $buffer, $initial);

        if (!$result instanceof FailureTracingResult) {
            return new SuccessfulEngineResult(
                value: $building === Building::Nothing
                    ? null
                    : $this->reduce($source, $result, $initial),
            );
        }

        $failure = new Failure(
            token: $result->token ?? $result->stoppedAt,
            expected: $result->expected,
            labelled: $result->labelled,
        );

        // A grammar that has read nothing has built nothing either, so there is
        // no fragment to report and the source is only described by the failure
        if ($result->length === 0) {
            return new FailureEngineResult($failure);
        }

        return new PartialEngineResult(
            value: $building === Building::Anything
                ? $this->reduce($source, $result, $initial)
                : null,
            stoppedAt: $result->stoppedAt,
            failure: $failure,
        );
    }

    /**
     * @param int<0, max> $initial
     */
    private function reduce(ReadableInterface $source, TracingResult $result, int $initial): mixed
    {
        return $this->reducers->createReducer($source, $initial)
            ->reduce($result);
    }
}
