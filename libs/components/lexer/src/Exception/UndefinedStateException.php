<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

/**
 * Occurs when a token transition references a state that has not been
 * defined in {@see Lexer::$states}.
 */
final class UndefinedStateException extends LexerException
{
    /**
     * @param non-empty-string $state
     * @param list<non-empty-string> $available
     */
    public static function becauseStateIsNotDefined(string $state, array $available): self
    {
        return new self(\sprintf(
            'The lexer state "%s" is not defined, available states are: %s',
            $state,
            $available === [] ? '*none*' : '"' . \implode('", "', $available) . '"',
        ));
    }
}
