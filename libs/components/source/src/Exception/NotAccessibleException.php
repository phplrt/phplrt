<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * The exception that occurs in case of file access errors, like "Permission Denied".
 *
 * @phpstan-type InternalErrorType array{
 *      type: int,
 *      message: string,
 *      file: string,
 *      line: int,
 *      ...
 *  }
 */
class NotAccessibleException extends \RuntimeException implements SourceExceptionInterface
{
    private const string DEFAULT_ERROR_MESSAGE = 'An unknown internal error occurred while accessing to the source';

    final public const int CODE_INTERNAL = 0x00;
    final public const int CODE_STREAM_WRITE = 0x01;
    final public const int CODE_STREAM_SEEK = 0x02;

    final public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param InternalErrorType|null $error
     */
    private static function createErrorFromArray(?array $error, ?\Throwable $prev = null): \Exception
    {
        if ($error === null) {
            return new \RuntimeException(self::DEFAULT_ERROR_MESSAGE);
        }

        $message = $error['message'];

        if ($message === '') {
            $message = self::DEFAULT_ERROR_MESSAGE;
        }

        return new \ErrorException(
            message: $message,
            code: 0,
            severity: $error['type'],
            filename: $error['file'],
            line: $error['line'],
            previous: $prev,
        );
    }

    /**
     * @param InternalErrorType|null $error
     */
    public static function becauseInternalErrorOccurs(?array $error, ?\Throwable $prev = null): static
    {
        $prev = self::createErrorFromArray($error, $prev);

        return new static($prev->getMessage(), self::CODE_INTERNAL, $prev);
    }

    public static function becauseStreamIsNotWritable(string $stream): self
    {
        $message = \sprintf('Can not write content data into "%s" stream', $stream);

        return new self($message, self::CODE_STREAM_WRITE);
    }

    public static function becauseStreamIsNotSeekable(string $stream): self
    {
        $message = \sprintf('The stream "%s" does not support offset (seek/rewind) changes', $stream);

        return new self($message, self::CODE_STREAM_SEEK);
    }
}
