<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Grammar\Adjacency;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Predicate;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\Tracing\Result\FailureTracingResult;
use Phplrt\Parser\Internal\Tracing\Result\SuccessfulTracingResult;

/**
 * Recognizes an input against a PEG grammar.
 *
 * The recognition descends the grammar rule by rule, and leaves a trace of
 * the rules that become a node of the result: the identifier of a rule when
 * it is entered, the tokens it reads, and its identifier negated when it is
 * left. A rule that is given up leaves nothing behind, so the trace holds
 * only what has been recognized and is reduced into values once the reading
 * is over.
 *
 * Whatever has stood in the way is reported by the {@see ErrorReport}, which
 * remembers the deepest position the reading has been rejected at and the
 * rules rejected there.
 *
 * Note: Every kind of rule is recognized inside the one {@see match()}
 *       method. A call is the most expensive thing the recognition does, and
 *       this method is called once per rule the reading goes through, so
 *       the shape of the method follows the cost of a call rather than the
 *       size of a page. The helpers it does call are the ones needed once
 *       per reading or once per failure, never once per rule.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 *
 * @phpstan-type MessageTableType array<int, non-empty-string>
 * @phpstan-type KeptTableType array<int, bool>
 * @phpstan-type StartPredictionTableType array<int, array<int, true>|null>
 * @phpstan-type ChoicePredictionTableType array<int, array<int, list<int>>>
 * @phpstan-type SequencePredictionTableType array<int, list<int>>
 */
final class RecursiveDescentTracer
{
    /**
     * The trace of the reading: rule identifiers on entering, tokens read,
     * negated rule identifiers on leaving.
     *
     * @var array<int<0, max>, int|TokenInterface>
     */
    private array $entries = [];

    /**
     * How much of the trace has been written.
     *
     * @var int<0, max>
     */
    private int $length = 0;

    /**
     * The tokens each rule may begin with, or {@see null} for a rule that
     * may begin with any token at all.
     *
     * @var StartPredictionTableType
     */
    private readonly array $startPrediction;

    /**
     * The rules that become a node of the result.
     *
     * @var KeptTableType
     */
    private readonly array $kept;

    /**
     * The tokens of the source the trace is being written for.
     */
    private BufferInterface $buffer;

    /**
     * What has stood in the way of that reading.
     */
    private ErrorReport $error;

    /**
     * @param StartPredictionTableType $startPrediction the tokens a rule may
     *        begin with, or {@see null} for a rule that may begin with any of
     *        them
     * @param KeptTableType $kept the rules that become a node of the result
     */
    public function __construct(
        /**
         * @var list<RuleInterface>
         */
        private readonly array $grammar,
        /**
         * The rules describing their own failure by a message of their own.
         *
         * Only the presence of a rule is asked about here: the message itself
         * is read once the recognition is over, by the one reporting the
         * error.
         *
         * @var MessageTableType
         */
        private readonly array $messages = [],
        array $kept = [],
        array $startPrediction = [],
        /**
         * The alternatives of an alternation worth trying, by the token the
         * reading is at. A negated identifier names a terminal reading that
         * very token.
         *
         * @var ChoicePredictionTableType
         */
        private readonly array $choicePrediction = [],
        /**
         * The elements of the sequences that may leave one of them out. A
         * negated identifier names the rule an optional element wraps.
         *
         * @var SequencePredictionTableType
         */
        private readonly array $sequencePrediction = [],
    ) {
        // A rule that leaves nothing behind is not written into the trace at
        // all, which is fewer entries to reduce afterward. A grammar that has
        // not been described is traced whole: every rule is taken for one that
        // matters.
        $this->kept = $kept === []
            ? \array_fill_keys(\array_keys($grammar), true)
            : $kept;

        // A grammar that has not been described is recognized all the same: it
        // reads exactly the same sources, only slower, and its failures are
        // reported deeper in the rules, since the rules alone do not say where
        // the reading was supposed to go.
        $this->startPrediction = $startPrediction === []
            ? \array_fill_keys(\array_keys($grammar), null)
            : $startPrediction;
    }

    /**
     * Reads the given tokens against the grammar, starting at the given rule.
     *
     * A tracer belongs to the parser that has built it and writes one trace at
     * a time: whatever it has read before is given up as soon as the next
     * reading begins, so the result of a reading is to be taken before the
     * next one starts.
     *
     * @param int<0, max> $initial the identifier of the rule the recognition
     *        starts at
     */
    public function trace(
        BufferInterface $buffer,
        int $initial,
    ): SuccessfulTracingResult|FailureTracingResult {
        if ($this->grammar === []) {
            // Fast-finish on empty grammar
            return new FailureTracingResult($buffer->current, $buffer->current);
        }

        $this->buffer = $buffer;
        $this->error = new ErrorReport($buffer, $this->grammar, $this->startPrediction);
        $this->entries = [];
        $this->length = 0;

        $isMatched = $this->match($initial);

        $current = $buffer->current;

        /**
         * What no rule has asked for is stepped over here as well: a token of
         * a channel of its own standing at the end of the source is not a part
         * of the source left unread.
         */
        if (!$current->channel instanceof Channel) {
            $current = $this->skipTrailing();
        }

        if ($isMatched && $current->channel === Channel::EndOfInput) {
            return new SuccessfulTracingResult($this->entries, $this->length);
        }

        return $this->createFailure($initial, $current, $isMatched);
    }

    /**
     * Recognizes the given rule at the position the reading is at.
     *
     * On success the reading stands past what the rule has read and the trace
     * holds what the rule has left behind. On failure the trace is as it was,
     * while the reading is given back by the rule that has moved it: a
     * terminal never moves it, and a sequence, a repetition and a predicate
     * give back what they have read on their own.
     *
     * A production that is given up returns right away, once the trace is
     * given back; a production that has been read falls through to the end
     * of the method, where it is closed in the trace.
     *
     * The method reads three kinds of shortcut the tables describe, each of
     * them worth a call per rule:
     *  - a terminal predicted by the very token the reading is at, which is
     *    read without being entered;
     *  - an optional element of a sequence, which is asked whether it stands
     *    a chance and entered only in case it does;
     *  - the body of a repetition, asked the very same way before each turn.
     */
    private function match(int $rule): bool
    {
        $buffer = $this->buffer;
        $definition = $this->grammar[$rule];
        $token = $buffer->current;

        // --- A terminal reads the token the reading is at -------------------

        if ($definition instanceof Lexeme) {
            $tokenId = $definition->tokenId;

            if ($token->id !== $tokenId) {
                // A token of a channel of its own is stepped over, unless a
                // rule asks for it by name; a token of a built-in channel is
                // the one the rule has been asked about
                if ($token->channel instanceof Channel || ($token = $this->stepOver($tokenId)) === null) {
                    // A terminal is reported at the very position it has been
                    // rejected at
                    if ($buffer->key >= $this->error->furthest) {
                        $this->error->record($rule);
                    }

                    return false;
                }
            }

            if ($definition->keep) {
                if ($this->kept[$rule]) {
                    $this->recordTerminalNode($rule, $token);
                } else {
                    $this->entries[$this->length++] = $token;
                }
            }

            $buffer->next();

            return true;
        }

        // --- A production is entered only in case it stands a chance --------

        $startTokens = $this->startPrediction[$rule];

        if ($startTokens !== null && !isset($startTokens[$token->id]) && $token->channel instanceof Channel) {
            /**
             * Only a failure ahead of the reported one is worth remembering:
             * the rules rejected alongside this one are the ones it contains,
             * so the tokens they may begin with are already among its own.
             */
            if ($buffer->key > $this->error->furthest) {
                $this->error->record($rule);
            }

            return false;
        }

        $origin = $buffer->key;
        $mark = $this->length;
        $isKept = $this->kept[$rule];

        if ($isKept) {
            $this->entries[$mark] = $rule;
            $this->length = $mark + 1;
        }

        // Note: The kind of a rule is told by an "instanceof" against a
        //       known class, which is cheaper than reading a precomputed kind
        //       off a table: measured ~5% faster on the whole reading.

        // --- A sequence reads its elements one after another ----------------

        if ($definition instanceof Concatenation) {
            foreach ($this->sequencePrediction[$rule] ?? $definition->ruleIds as $inner) {
                if ($inner >= 0) {
                    if ($this->match($inner)) {
                        continue;
                    }

                    /**
                     * The place a rule is written at is what its message
                     * describes, so the failure is reported before the input
                     * is given back: afterwards the reading is at the beginning
                     * of the sequence instead of the element that has broken it.
                     */
                    if (isset($this->messages[$inner])) {
                        $this->error->label($inner);
                    }

                    if ($buffer->key !== $origin) {
                        $buffer->seek($origin);
                    }

                    $this->length = $mark;

                    return false;
                }

                // An element that may be left out: the rule it wraps is entered
                // only in case it stands a chance, and the sequence goes on
                // whether it has been read or not
                $inner = -$inner - 1;
                $element = $this->grammar[$inner];
                $token = $buffer->current;

                if ($element instanceof Lexeme) {
                    if ($token->id !== $element->tokenId && $token->channel instanceof Channel) {
                        if ($buffer->key >= $this->error->furthest) {
                            $this->error->record($inner);
                        }

                        continue;
                    }
                } else {
                    $elementTokens = $this->startPrediction[$inner];

                    if ($elementTokens !== null && !isset($elementTokens[$token->id]) && $token->channel instanceof Channel) {
                        if ($buffer->key > $this->error->furthest) {
                            $this->error->record($inner);
                        }

                        continue;
                    }
                }

                $before = $buffer->key;

                if (!$this->match($inner) && $buffer->key !== $before) {
                    $buffer->seek($before);
                }
            }

        // --- An alternation reads the first of its alternatives that fits ---
        } elseif ($definition instanceof Alternation) {
            /**
             * Which of the alternatives are worth entering is decided by the
             * token the reading is at: every other one would have been rejected
             * by that very token as soon as it was entered. An alternation the
             * grammar says nothing about is recognized by trying every
             * alternative it has.
             */
            $alternatives = $this->choicePrediction[$rule][$token->id]
                ?? $definition->ruleIds;

            foreach ($alternatives as $inner) {
                if ($inner >= 0) {
                    if ($this->match($inner)) {
                        if ($isKept) {
                            $this->entries[$this->length++] = -$rule - 1;
                        }

                        return true;
                    }

                    // An alternative describing its own failure is reported the
                    // way an element of a sequence is
                    if (isset($this->messages[$inner])) {
                        $this->error->label($inner);
                    }

                    // Most alternatives are rejected by their start token and
                    // read nothing at all, so the reading is already where it
                    // would be given back to
                    if ($buffer->key !== $origin) {
                        $buffer->seek($origin);
                    }

                    continue;
                }

                // A terminal predicted by the very token the reading is at
                // reads it without being entered: there is nothing left to try
                $inner = -$inner - 1;

                /** @var Lexeme $element */
                $element = $this->grammar[$inner];

                if ($element->keep) {
                    if ($this->kept[$inner]) {
                        $this->recordTerminalNode($inner, $token);
                    } else {
                        $this->entries[$this->length++] = $token;
                    }
                }

                $buffer->next();

                if ($isKept) {
                    $this->entries[$this->length++] = -$rule - 1;
                }

                return true;
            }

            /**
             * The alternatives left out are the ones the token the input is at
             * rejects, and the tokens this rule may begin with are all of
             * theirs, so it is reported in place of every one of them.
             */
            if ($buffer->key > $this->error->furthest) {
                $this->error->record($rule);
            }

            $this->length = $mark;

            return false;

        // --- A repetition reads its body as long as the body reads ----------
        } elseif ($definition instanceof Repetition) {
            $inner = $definition->ruleId;
            $max = $definition->max;
            $matched = 0;

            // The last turn is the one that is given up, and most of the time
            // by the very first token, so the body is asked whether it stands
            // a chance before it is entered
            $bodyTokens = $this->startPrediction[$inner];

            while ($matched < $max) {
                $before = $buffer->key;

                if ($bodyTokens !== null) {
                    $token = $buffer->current;

                    if (!isset($bodyTokens[$token->id]) && $token->channel instanceof Channel) {
                        // The body is remembered the way it would have
                        // remembered itself: a terminal at the very position
                        // it is rejected at, a production only ahead of the
                        // reported failure
                        $furthest = $this->error->furthest;

                        if ($before > $furthest || ($before === $furthest && $this->grammar[$inner] instanceof Lexeme)) {
                            $this->error->record($inner);
                        }

                        break;
                    }
                }

                if (!$this->match($inner)) {
                    break;
                }

                // A body reading the empty input would be read forever, so the
                // repetition stops as soon as the reading stalls
                if ($buffer->key === $before) {
                    break;
                }

                ++$matched;
            }

            if ($matched < $definition->min) {
                if ($buffer->key !== $origin) {
                    $buffer->seek($origin);
                }

                $this->length = $mark;

                return false;
            }

        // --- The rare kinds ------------------------------------------------
        } elseif ($definition instanceof Optional) {
            if (!$this->match($definition->ruleId) && $buffer->key !== $origin) {
                $buffer->seek($origin);
            }
        } elseif ($definition instanceof Predicate) {
            $isMatched = $this->match($definition->ruleId);

            // A predicate only looks at what comes next, so both the reading
            // and the trace are given back, no matter what has been recognized
            if ($buffer->key !== $origin) {
                $buffer->seek($origin);
            }

            if ($isMatched !== $definition->isExpected) {
                $this->length = $mark;

                return false;
            }

            $this->length = $isKept ? $mark + 1 : $mark;
        } elseif ($definition instanceof Adjacency) {
            $previous = $buffer->lookBehind();

            if (($previous->offset + $previous->size === $token->offset) !== $definition->isExpected) {
                $this->length = $mark;

                return false;
            }
        } else {
            throw new \LogicException(\sprintf(
                'Unsupported grammar rule %s',
                \get_debug_type($definition),
            ));
        }

        // --- The production has been read -----------------------------------

        if ($isKept) {
            $this->entries[$this->length++] = -$rule - 1;
        }

        return true;
    }

    /**
     * Writes a terminal that is a node of its own into the trace, the way an
     * ordinary rule reading a single token is written, so it is reduced the
     * very same way.
     */
    private function recordTerminalNode(int $rule, TokenInterface $token): void
    {
        $length = $this->length;

        $this->entries[$length] = $rule;
        $this->entries[$length + 1] = $token;
        $this->entries[$length + 2] = -$rule - 1;

        $this->length = $length + 3;
    }

    /**
     * Describes the reading that has not reached the end of the source.
     *
     * The rule the recognition starts at is contained by nothing, so its own
     * failure is reported here rather than by the rule above it. A rule that
     * has been recognized without reading the source to its end has not
     * described the source either, so it is reported the very same way.
     */
    private function createFailure(int $initial, TokenInterface $stoppedAt, bool $isMatched): FailureTracingResult
    {
        if (isset($this->messages[$initial])) {
            $this->error->labelInitial($initial);
        }

        return $this->error->toFailureResult(
            stoppedAt: $stoppedAt,
            entries: $this->entries,
            length: $isMatched ? $this->length : 0,
        );
    }

    /**
     * Walks the reading past the tokens no rule has asked for, once it has
     * nothing left to recognize.
     */
    private function skipTrailing(): TokenInterface
    {
        $buffer = $this->buffer;

        do {
            $before = $buffer->key;

            $buffer->next();
            // A stream ending with such a token has nothing left to step onto,
            // and the cursor stays where it is
        } while ($buffer->key !== $before && !$buffer->current->channel instanceof Channel);

        return $buffer->current;
    }

    /**
     * Walks the reading past the tokens of the channels a grammar has declared
     * on its own, up to the token of the given kind.
     */
    private function stepOver(int $tokenId): ?TokenInterface
    {
        $buffer = $this->buffer;
        $rollback = $buffer->key;

        do {
            $before = $buffer->key;

            $buffer->next();

            $token = $buffer->current;

            if ($token->id === $tokenId) {
                return $token;
            }
        } while ($buffer->key !== $before && !$token->channel instanceof Channel);

        $buffer->seek($rollback);

        return null;
    }
}
