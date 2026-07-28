<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * Occurs when the source code cannot be read at all, so the analysis has not
 * even started.
 */
final class LexerSourceException extends LexerException
{
    public static function becauseSourceIsNotReadable(SourceExceptionInterface $e): self
    {
        $message = \sprintf('The source code cannot be read: %s', $e->getMessage());

        return new self($message, previous: $e);
    }
}
