<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\ParseTree\Lookahead;
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
     * @var array<int<0, max>, int>
     */
    private array $types = [];

    /**
     * @var array<int<0, max>, int|TokenInterface>
     */
    private array $references = [];

    /**
     * @var int<0, max>
     */
    private int $length = 0;

    private readonly ErrorReport $error;

    /**
     * @var array<int, array<int, true>>
     */
    private readonly array $first;

    /**
     * @var array<int, bool>
     */
    private readonly array $nullable;

    private function __construct(
        /**
         * @var list<RuleInterface>
         */
        private readonly array $grammar,
        private readonly BufferInterface $buffer,
        Lookahead $lookahead,
        /**
         * @var array<int, bool>
         */
        private readonly array $kept,
    ) {
        $this->error = new ErrorReport($buffer);
        $this->first = $lookahead->first;
        $this->nullable = $lookahead->nullable;
    }

    /**
     * Recognizes the given rule against the input.
     *
     * @param list<RuleInterface> $grammar
     * @param int<0, max> $initial
     * @param array<int, bool> $kept
     */
    public static function trace(
        array $grammar,
        int $initial,
        BufferInterface $buffer,
        Lookahead $lookahead,
        array $kept,
    ): Success|Failure {
        if ($grammar === []) {
            // Fast-finish on empty grammar
            return new Failure($buffer->current);
        }

        $self = new self($grammar, $buffer, $lookahead, $kept);

        if ($self->match($initial) && $self->isEndOfInput()) {
            return new Success(
                types: $self->types,
                references: $self->references,
                length: $self->length,
            );
        }

        return $self->error->finish();
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

        if ($definition instanceof Lexeme) {
            return $this->matchLexeme($rule, $definition);
        }

        // The rule requires a token it cannot start with, so there is nothing
        // to recognize
        if (!isset($this->first[$rule][$this->buffer->current->id]) && !$this->nullable[$rule]) {
            return false;
        }

        $mark = $this->length;
        $kept = $this->kept[$rule];

        if ($kept) {
            $this->types[$mark] = Success::TYPE_ENTER;
            $this->references[$mark] = $rule;
            $this->length = $mark + 1;
        }

        $matched = match (true) {
            $definition instanceof Concatenation => $this->matchConcatenation($definition),
            $definition instanceof Alternation => $this->matchAlternation($definition),
            $definition instanceof Optional => $this->matchOptional($definition),
            $definition instanceof Repetition => $this->matchRepetition($definition),
            default => throw new \LogicException(\sprintf(
                'Unsupported grammar rule %s',
                $definition::class,
            )),
        };

        if (!$matched) {
            $this->length = $mark;

            return false;
        }

        if ($kept) {
            $length = $this->length;

            $this->types[$length] = Success::TYPE_LEAVE;
            $this->references[$length] = $rule;
            $this->length = $length + 1;
        }

        return true;
    }

    private function matchLexeme(int $rule, Lexeme $definition): bool
    {
        $buffer = $this->buffer;
        $token = $buffer->current;
        $id = $definition->tokenId;

        if ($token->id === $id) {
            if ($definition->keep) {
                // The terminal is recorded as an ordinary rule containing a
                // single token, so it can be reduced in the same way
                $length = $this->length;

                $this->types[$length] = Success::TYPE_ENTER;
                $this->references[$length] = $rule;
                $this->types[$length + 1] = Success::TYPE_TOKEN;
                $this->references[$length + 1] = $token;
                $this->types[$length + 2] = Success::TYPE_LEAVE;
                $this->references[$length + 2] = $rule;

                $this->length = $length + 3;
            }

            $buffer->next();

            return true;
        }

        $this->error->record($id);

        return false;
    }

    private function matchConcatenation(Concatenation $rule): bool
    {
        $buffer = $this->buffer;
        $rollback = $buffer->key;

        foreach ($rule->rules as $inner) {
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

            // A nullable inner rule would match forever without consuming a
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
