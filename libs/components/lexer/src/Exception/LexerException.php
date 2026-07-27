<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

class LexerException extends \RuntimeException implements LexerExceptionInterface
{
    /**
     * Occurs when the source code cannot be read at all, so the analysis has
     * not even started.
     */
    public static function becauseSourceIsNotReadable(SourceExceptionInterface $e): self
    {
        return new self(\sprintf(
            'The source code cannot be read: %s',
            $e->getMessage(),
        ), previous: $e);
    }
}
