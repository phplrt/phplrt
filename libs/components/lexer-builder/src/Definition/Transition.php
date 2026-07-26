<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Definition;

/**
 * Describes a lexer state change triggered by a token.
 *
 * The transition is applied AFTER the token has been emitted, so the token
 * itself always belongs to the state it was matched in.
 */
final readonly class Transition implements \Stringable
{
    private function __construct(
        public TransitionType $type,
        /**
         * The target state name of the {@see TransitionType::Enter}
         * transition, or {@see null} for {@see TransitionType::Exit}.
         *
         * @var non-empty-string|null
         */
        public ?string $state = null,
    ) {}

    /**
     * @param non-empty-string $state
     */
    public static function enter(string $state): self
    {
        return new self(TransitionType::Enter, $state);
    }

    public static function exit(): self
    {
        return new self(TransitionType::Exit);
    }

    public function __toString(): string
    {
        return match ($this->type) {
            TransitionType::Enter => \sprintf('enters the "%s" state', (string) $this->state),
            TransitionType::Exit => 'leaves the current state',
        };
    }
}
