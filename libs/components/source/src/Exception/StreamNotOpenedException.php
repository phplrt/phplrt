<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Source\Exception\Internal\PhpError;

/**
 * A stream cannot be opened.
 *
 * @phpstan-import-type PhpErrorType from PhpError
 */
final class StreamNotOpenedException extends NotReadableException
{
    /**
     * @psalm-taint-sink file $uri
     * @param non-empty-string $uri
     * @param array|null $error the error the engine has left behind
     * @phpstan-param PhpErrorType|null $error
     */
    public static function becauseStreamCannotBeOpened(
        string $uri,
        ?array $error = null,
        ?\Throwable $prev = null,
    ): self {
        $message = 'The stream "%s" cannot be opened';

        return new self(
            \sprintf($message, $uri),
            previous: PhpError::toThrowable($error, $prev),
        );
    }
}
