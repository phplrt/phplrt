<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a grammar is written in a format the compiler does not read.
 */
final class UnsupportedFormatException extends UnsupportedSyntaxException
{
    /**
     * @param non-empty-string $extension
     */
    public static function becauseFormatIsNotSupported(
        ReadableInterface $source,
        string $extension,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('Grammar files written in the "%s" format are not supported', $extension);

        return new self(
            source: $source,
            offset: 0,
            message: $message,
            previous: $previous,
        );
    }
}
