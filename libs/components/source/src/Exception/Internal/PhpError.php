<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception\Internal;

/**
 * The error the engine has left behind at the moment a source operation
 * has failed.
 *
 * @phpstan-type PhpErrorType array{
 *     type: int,
 *     message: string,
 *     file: string,
 *     line: int,
 *     ...
 * }
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Source\Exception
 */
final class PhpError
{
    private const string DEFAULT_MESSAGE = 'An unknown internal error occurred while accessing to the source';

    /**
     * Gets the error as a throwable one, which is what carries it over as the
     * reason of a source failure.
     *
     * @param PhpErrorType|null $error
     */
    public static function toThrowable(?array $error, ?\Throwable $previous = null): \Exception
    {
        if ($error === null) {
            return new \RuntimeException(self::DEFAULT_MESSAGE, previous: $previous);
        }

        return new \ErrorException(
            message: $error['message'] === '' ? self::DEFAULT_MESSAGE : $error['message'],
            code: 0,
            severity: $error['type'],
            filename: $error['file'],
            line: $error['line'],
            previous: $previous,
        );
    }
}
