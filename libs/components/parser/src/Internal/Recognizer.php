<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal;

use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\Tracing\ErrorReport;
use Phplrt\Parser\Internal\Tracing\Result\Failure;
use Phplrt\Parser\Internal\Tracing\Result\Success;
use Phplrt\Parser\Internal\Tracing\Trace;

/**
 * Recognizes an input against a PEG grammar.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final readonly class Recognizer
{
    private Trace $trace;

    private ErrorReport $error;

    public function __construct(
        /**
         * @var array<int, RuleInterface>
         */
        private array $grammar,
        private BufferInterface $buffer,
    ) {
        $this->trace = new Trace();
        $this->error = new ErrorReport($buffer);
    }

    /**
     * Recognizes the given rule against the input.
     */
    public function recognize(int $rule): Success|Failure
    {
        return $this->match($rule)
            ? $this->trace->finish()
            : $this->error->finish();
    }

    private function match(int $rule): bool
    {
        $definition = $this->grammar[$rule];

        if ($definition instanceof Lexeme) {
            return $this->matchLexeme($definition);
        }

        $mark = $this->trace->mark();
        $this->trace->enter($rule);

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

        if ($matched) {
            $this->trace->leave($rule);
        } else {
            $this->trace->rewind($mark);
        }

        return $matched;
    }

    private function matchLexeme(Lexeme $rule): bool
    {
        $token = $this->buffer->current;

        if ($token->id === $rule->tokenId) {
            if ($rule->keep) {
                $this->trace->token($token);
            }

            $this->buffer->next();

            return true;
        }

        $this->error->record($rule->tokenId);

        return false;
    }

    private function matchConcatenation(Concatenation $rule): bool
    {
        $rollback = $this->buffer->key;

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
        $rollback = $this->buffer->key;

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
        $rollback = $this->buffer->key;

        if (!$this->match($rule->ruleId)) {
            $this->buffer->seek($rollback);
        }

        return true;
    }

    private function matchRepetition(Repetition $rule): bool
    {
        $rollback = $this->buffer->key;
        $matched = 0;

        while ($matched < $rule->max) {
            $before = $this->buffer->key;

            if (!$this->match($rule->ruleId)) {
                break;
            }

            // A nullable inner rule would match forever without consuming a
            // single token, so the repetition stops as soon as it stalls.
            if ($this->buffer->key === $before) {
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
}
