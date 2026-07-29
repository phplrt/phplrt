<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Definition;

/**
 * Describes what a token does to the reading.
 *
 * The transition is applied AFTER the token has been read, so the token itself
 * always belongs to the lexer that has read it.
 */
final readonly class Transition implements \Stringable
{
    private function __construct(
        public TransitionType $type,
        /**
         * The name of the lexer the {@see TransitionType::Enter} transition
         * hands the reading over to, or {@see null} for
         * {@see TransitionType::Exit}.
         *
         * @var non-empty-string|null
         */
        public ?string $lexer = null,
    ) {}

    /**
     * @param non-empty-string $lexer
     */
    public static function enter(string $lexer): self
    {
        return new self(TransitionType::Enter, $lexer);
    }

    public static function exit(): self
    {
        return new self(TransitionType::Exit);
    }

    public function __toString(): string
    {
        return match ($this->type) {
            TransitionType::Enter => \sprintf('is read along with the "%s" lexer', (string) $this->lexer),
            TransitionType::Exit => 'ends the reading',
        };
    }
}
