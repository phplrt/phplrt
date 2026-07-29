<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * Occurs when the source code cannot be read at all, so there is nothing to
 * parse.
 */
final class ParserSourceException extends ParserException
{
    public static function becauseSourceIsNotReadable(SourceExceptionInterface $e): self
    {
        $message = \sprintf('The source code cannot be read: %s', $e->getMessage());

        return new self($message, previous: $e);
    }
}
