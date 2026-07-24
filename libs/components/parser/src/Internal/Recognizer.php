<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * A PEG recognizer.
 *
 * Walks the grammar (a map of inert rule definitions) over the token buffer and
 * reports whether the input matches, WITHOUT materializing anything. Every rule
 * either advances the buffer on success or leaves it exactly where it started on
 * failure, so an ordered choice may freely backtrack.
 *
 * This is the first (recognition) pass. Recording an enter/exit trace on top of
 * it — the input of the future reduction pass — does not change the control flow
 * below: a trace is appended on success and truncated back on backtracking, in
 * lockstep with the buffer rollback.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final class Recognizer
{
    /**
     * The furthest buffer position a terminal match has failed at. It is the
     * single most useful hint for an error message, since a plain PEG failure
     * carries no location on its own.
     */
    private int $furthest = -1;

    private ?TokenInterface $furthestToken = null;

    /**
     * The token identifiers expected at the {@see self::$furthest} position.
     *
     * @var array<int, int>
     */
    private array $expected = [];

    public function __construct(
        /**
         * @var array<int, RuleInterface>
         */
        private readonly array $grammar,
        private readonly BufferInterface $buffer,
    ) {}

    /**
     * Returns {@see true} in case the given rule matches at the current buffer
     * position, leaving the buffer right after the consumed tokens.
     */
    public function recognize(int $rule): bool
    {
        return $this->match($rule);
    }

    /**
     * The token the analysis got stuck on, or {@see null} in case no terminal
     * has ever failed.
     */
    public function getFurthestToken(): ?TokenInterface
    {
        return $this->furthestToken;
    }

    /**
     * @return list<int>
     */
    public function getExpectedTokens(): array
    {
        return \array_values($this->expected);
    }

    private function match(int $rule): bool
    {
        $definition = $this->grammar[$rule];

        return match (true) {
            $definition instanceof Lexeme => $this->matchLexeme($definition),
            $definition instanceof Concatenation => $this->matchConcatenation($definition),
            $definition instanceof Alternation => $this->matchAlternation($definition),
            $definition instanceof Optional => $this->matchOptional($definition),
            $definition instanceof Repetition => $this->matchRepetition($definition),
            default => throw new \LogicException(\sprintf(
                'Unsupported grammar rule %s',
                $definition::class,
            )),
        };
    }

    private function matchLexeme(Lexeme $rule): bool
    {
        if ($this->buffer->current()->id === $rule->tokenId) {
            $this->buffer->next();

            return true;
        }

        $this->recordFailure($rule->tokenId);

        return false;
    }

    private function matchConcatenation(Concatenation $rule): bool
    {
        $rollback = $this->buffer->key();

        foreach ($rule->rules as $inner) {
            if (!$this->match($inner)) {
                $this->buffer->seek($rollback);

                return false;
            }
        }

        return true;
    }

    private function matchAlternation(Alternation $rule): bool
    {
        $rollback = $this->buffer->key();

        foreach ($rule->ruleIds as $inner) {
            if ($this->match($inner)) {
                return true;
            }

            $this->buffer->seek($rollback);
        }

        return false;
    }

    private function matchOptional(Optional $rule): bool
    {
        $rollback = $this->buffer->key();

        if (!$this->match($rule->ruleId)) {
            $this->buffer->seek($rollback);
        }

        return true;
    }

    private function matchRepetition(Repetition $rule): bool
    {
        $rollback = $this->buffer->key();
        $matched = 0;

        while ($matched < $rule->max) {
            $before = $this->buffer->key();

            if (!$this->match($rule->ruleId)) {
                break;
            }

            // A nullable inner rule would match forever without consuming a
            // single token, so the repetition stops as soon as it stalls.
            if ($this->buffer->key() === $before) {
                break;
            }

            ++$matched;
        }

        if ($matched < $rule->min) {
            $this->buffer->seek($rollback);

            return false;
        }

        return true;
    }

    private function recordFailure(int $tokenId): void
    {
        $position = $this->buffer->key();

        if ($position > $this->furthest) {
            $this->furthest = $position;
            $this->furthestToken = $this->buffer->current();
            $this->expected = [$tokenId => $tokenId];

            return;
        }

        if ($position === $this->furthest) {
            $this->expected[$tokenId] = $tokenId;
        }
    }
}
