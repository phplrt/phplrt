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
use Phplrt\Parser\Internal\Tracing\Result\Failure;
use Phplrt\Parser\Internal\Tracing\Result\Success;

/**
 * Recognizes an input against a PEG grammar.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
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

    private readonly ErrorReport $error;

    private function __construct(
        /**
         * @var list<RuleInterface>
         */
        private readonly array $grammar,
        private readonly BufferInterface $buffer,
        /**
         * Empty in case the lookahead is unknown, which admits every rule.
         *
         * @var array<int, array<int, true>>
         */
        private readonly array $startTokens,
        /**
         * @var array<int, bool>
         */
        private readonly array $matchesEmptyInput,
        /**
         * @var array<int, bool>
         */
        private readonly array $presentInTree,
    ) {
        $this->error = new ErrorReport($buffer, $grammar, $startTokens);
    }

    /**
     * Recognizes the given rule against the input.
     *
     * The "matchesEmptyInput" and "presentInTree" tables cover every rule of
     * the grammar, an unknown one is passed as a table admitting all of them.
     *
     * @param list<RuleInterface> $grammar
     * @param int<0, max> $initial
     * @param array<int, array<int, true>> $startTokens
     * @param array<int, bool> $matchesEmptyInput
     * @param array<int, bool> $presentInTree
     */
    public static function trace(
        array $grammar,
        int $initial,
        BufferInterface $buffer,
        array $startTokens,
        array $matchesEmptyInput,
        array $presentInTree,
    ): Success|Failure {
        if ($grammar === []) {
            // Fast-finish on empty grammar
            return new Failure($buffer->current);
        }

        $self = new self(
            grammar: $grammar,
            buffer: $buffer,
            startTokens: $startTokens,
            matchesEmptyInput: $matchesEmptyInput,
            presentInTree: $presentInTree,
        );

        if (!$self->match($initial)) {
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
            $buffer = $this->buffer;
            $token = $buffer->current;
            $id = $definition->tokenId;

            if ($token->id !== $id) {
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

                    $this->length += 3;
                } else {
                    $this->entries[$length] = $token;
                    ++$this->length;
                }
            }

            $buffer->next();

            return true;
        }

        // The rule requires a token it cannot start with, so there is nothing
        // to recognize
        if (!isset($this->startTokens[$rule][$this->buffer->current->id]) && !$this->matchesEmptyInput[$rule]) {
            /**
             * Only a failure ahead of the reported one is worth remembering:
             * the rules rejected alongside this one are the ones it contains,
             * so the tokens they may begin with are already among its own.
             */
            if ($this->buffer->key > $this->error->furthest) {
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

        $matched = match (true) {
            $definition instanceof Concatenation => $this->matchConcatenation($definition),
            $definition instanceof Alternation => $this->matchAlternation($definition),
            $definition instanceof Optional => $this->matchOptional($definition),
            $definition instanceof Repetition => $this->matchRepetition($definition),
            $definition instanceof Predicate => $this->matchPredicate($definition),
            default => throw new \LogicException(\sprintf(
                'Unsupported grammar rule %s',
                $definition::class,
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
                $buffer->seek($rollback);

                return false;
            }
        }

        return true;
    }

    private function matchAlternation(Alternation $rule): bool
    {
        $buffer = $this->buffer;
        $rollback = $buffer->key;

        foreach ($rule->ruleIds as $inner) {
            if ($this->match($inner)) {
                return true;
            }

            $buffer->seek($rollback);
        }

        return false;
    }

    private function matchOptional(Optional $rule): bool
    {
        $buffer = $this->buffer;
        $rollback = $buffer->key;

        if (!$this->match($rule->ruleId)) {
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
        $buffer->seek($rollback);
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
            $buffer->seek($rollback);

            return false;
        }

        return true;
    }
}
