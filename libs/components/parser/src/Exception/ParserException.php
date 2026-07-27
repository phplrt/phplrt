<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

class ParserException extends \RuntimeException implements
    ParserExceptionInterface
{
    /**
     * Occurs when the source code cannot be read at all, so there is nothing
     * to parse.
     */
    public static function becauseSourceIsNotReadable(SourceExceptionInterface $e): self
    {
        return new self(\sprintf(
            'The source code cannot be read: %s',
            $e->getMessage(),
        ), previous: $e);
    }
}
