<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Source\Exception\Internal\PhpError;

/**
 * The engine has reported a failure of its own while the data of a stream was
 * being taken out.
 *
 * @phpstan-import-type PhpErrorType from PhpError
 */
final class StreamReadingException extends NotReadableException
{
    /**
     * @param array|null $error the error the engine has left behind
     * @phpstan-param PhpErrorType|null $error
     */
    public static function becauseStreamCannotBeRead(?array $error, ?\Throwable $prev = null): self
    {
        $reason = PhpError::toThrowable($error, $prev);

        $message = 'The stream cannot be read: %s';

        return new self(\sprintf($message, $reason->getMessage()), previous: $reason);
    }
}
