<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Predicate;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\Tracing\ErrorReport;
use Phplrt\Parser\Internal\Tracing\GrammarTable;
use Phplrt\Parser\Internal\Tracing\Result\Failure;
use Phplrt\Parser\Internal\Tracing\Result\Success;

/**
 * Recognizes an input against a PEG grammar.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 *
 * @phpstan-import-type LookaheadTableType from GrammarTable
 * @phpstan-import-type KeptTableType from GrammarTable
 * @phpstan-import-type ChoicePredictionTableType from GrammarTable
 */
final class RecursiveDescentTracer
{
    /**
     * @var array<int<0, max>, int|TokenInterface>
     */
    private array $entries = [];

    /**
     * @var int<0, max>
     */
    private int $length = 0;

    /**
     * @var list<RuleInterface>
     */
    private readonly array $grammar;

    /**
     * @var LookaheadTableType
     */
    private readonly array $lookahead;

    /**
     * @var KeptTableType
     */
    private readonly array $presentInTree;

    /**
     * @var ChoicePredictionTableType
     */
    private readonly array $branchesByToken;

    private readonly ErrorReport $error;

    private function __construct(
        GrammarTable $table,
        private readonly BufferInterface $buffer,
    ) {
        $this->grammar = $table->rules;
        $this->lookahead = $table->lookahead;
        $this->presentInTree = $table->presentInTree;
        $this->branchesByToken = $table->branchesByToken;

        $this->error = new ErrorReport($buffer, $table->rules, $table->lookahead);
    }

    public static function trace(GrammarTable $table, BufferInterface $buffer): Success|Failure
    {
        if ($table->rules === []) {
            // Fast-finish on empty grammar
            return new Failure($buffer->current);
        }

        $self = new self($table, $buffer);

        if (!$self->match($table->initial)) {
            return $self->error->finish();
        }

        return new Success(
            entries: $self->entries,
            length: $self->length,
            /**
             * The rules are greedy, so whatever the recognition has stopped at
             * is the first token the grammar cannot read.
             */
            stoppedAt: $buffer->current,
            /**
             * An input that has not been read to its end has broken somewhere,
             * and where it has broken is not where the reading has stopped, so
             * the report is kept for whoever has to say what is wrong.
             */
            furthest: $self->isEndOfInput() ? null : $self->error->finish(),
        );
    }

    /**
     * The whole input is recognized only in case it is completely consumed.
     */
    private function isEndOfInput(): bool
    {
        return $this->buffer->current->channel === Channel::EndOfInput;
    }

    private function match(int $rule): bool
    {
        $buffer = $this->buffer;
        $definition = $this->grammar[$rule];

        // A `Lexeme` is the most common matching rule in the parser. We could
        // move everything inside this `if` statement into a separate method,
        // but that would result in a performance loss of about ~5%.
        //
        // Therefore, it's better to sacrifice a little readability for a
        // small boost.
        //
        // TODO In the future, this code should be rewritten from a recursive
        //      algorithm to a full-fledged state machine and all the rules
        //      should be inlined. This should, in theory, further speed up
        //      the code (do this and then benchmark it).
        if ($definition instanceof Lexeme) {
            $token = $buffer->current;

            if ($token->id !== $definition->tokenId) {
                $error = $this->error;

                // A failure behind the reported one changes nothing
                if ($buffer->key >= $error->furthest) {
                    $error->record($rule);
                }

                return false;
            }

            if ($definition->keep) {
                $length = $this->length;

                if ($this->presentInTree[$rule]) {
                    // The terminal is recorded as an ordinary rule containing a
                    // single token, so it can be reduced in the same way
                    $this->entries[$length] = $rule;
                    $this->entries[$length + 1] = $token;
                    $this->entries[$length + 2] = -$rule - 1;

                    $this->length = $length + 3;
                } else {
                    $this->entries[$length] = $token;
                    $this->length = $length + 1;
                }
            }

            $buffer->next();

            return true;
        }

        $lookahead = $this->lookahead[$rule];

        // The rule requires a token it cannot start with, so there is nothing
        // to recognize
        if ($lookahead !== null && !isset($lookahead[$buffer->current->id])) {
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

        $mark = $this->length;
        $presentInTree = $this->presentInTree[$rule];

        if ($presentInTree) {
            $this->entries[$mark] = $rule;
            $this->length = $mark + 1;
        }

        // We could answer "which rule is this?" once, before parsing, and just
        // read the answer from a table here. Don't bother: an `instanceof`
        // against a known class turns out to be cheaper than the array lookup
        // such a table would cost, and doing it that way measured ~5% slower.
        $matched = match (true) {
            $definition instanceof Concatenation => $this->matchConcatenation($definition),
            $definition instanceof Alternation => $this->matchAlternation($rule, $definition),
            $definition instanceof Optional => $this->matchOptional($definition),
            $definition instanceof Repetition => $this->matchRepetition($definition),
            $definition instanceof Predicate => $this->matchPredicate($definition),
            default => throw new \LogicException(\sprintf(
                'Unsupported grammar rule %s',
                \get_debug_type($definition),
            )),
        };

        if (!$matched) {
            $this->length = $mark;

            return false;
        }

        if ($presentInTree) {
            $length = $this->length;

            $this->entries[$length] = -$rule - 1;
            $this->length = $length + 1;
        }

        return true;
    }

    private function matchConcatenation(Concatenation $rule): bool
    {
        $buffer = $this->buffer;
        $rollback = $buffer->key;

        foreach ($rule->ruleIds as $inner) {
            if (!$this->match($inner)) {
                if ($buffer->key !== $rollback) {
                    $buffer->seek($rollback);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Recognizes the first of the alternatives that reads the input.
     *
     * Which of them are worth entering is decided by the token the input is at:
     * every other one would have been rejected by that very token as soon as it
     * was entered, and more than half of them are. An alternation the grammar
     * says nothing about is recognized by trying every alternative it has.
     */
    private function matchAlternation(int $rule, Alternation $definition): bool
    {
        $buffer = $this->buffer;
        $rollback = $buffer->key;

        $branches = $this->branchesByToken[$rule][$buffer->current->id]
            ?? $definition->ruleIds;

        foreach ($branches as $inner) {
            if ($this->match($inner)) {
                return true;
            }

            // Most alternatives are rejected by their start token and so read
            // nothing at all, which means the buffer is already where we would
            // rewind it to. That is about 3/4 of all rollbacks during a parse,
            // and asking first is cheaper than making the call.
            if ($buffer->key !== $rollback) {
                $buffer->seek($rollback);
            }
        }

        /**
         * The alternatives left out are the ones the token the input is at
         * rejects, and the tokens this rule may begin with are all of theirs,
         * so it is reported in place of every one of them.
         */
        if ($buffer->key > $this->error->furthest) {
            $this->error->record($rule);
        }

        return false;
    }

    private function matchOptional(Optional $rule): bool
    {
        $buffer = $this->buffer;
        $rollback = $buffer->key;

        if (!$this->match($rule->ruleId) && $buffer->key !== $rollback) {
            $buffer->seek($rollback);
        }

        return true;
    }

    private function matchPredicate(Predicate $rule): bool
    {
        $buffer = $this->buffer;
        $rollback = $buffer->key;
        $mark = $this->length;

        $matched = $this->match($rule->ruleId);

        /**
         * A predicate only looks at what comes next, so both the input and the
         * trace are rolled back, no matter what has been recognized.
         */
        if ($buffer->key !== $rollback) {
            $buffer->seek($rollback);
        }

        $this->length = $mark;

        return $matched === $rule->isExpected;
    }

    private function matchRepetition(Repetition $rule): bool
    {
        $buffer = $this->buffer;
        $ruleId = $rule->ruleId;
        $max = $rule->max;

        $rollback = $buffer->key;
        $matched = 0;

        while ($matched < $max) {
            $before = $buffer->key;

            if (!$this->match($ruleId)) {
                break;
            }

            // An inner rule matching empty input would match forever without consuming a
            // single token, so the repetition stops as soon as it stalls.
            if ($buffer->key === $before) {
                break;
            }

            ++$matched;
        }

        if ($matched < $rule->min) {
            if ($buffer->key !== $rollback) {
                $buffer->seek($rollback);
            }

            return false;
        }

        return true;
    }
}
