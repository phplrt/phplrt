<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\SourceExceptionInterface;

/**
 * The exception that occurs in case of file access errors, like "Permission Denied".
 */
class NotAccessibleException extends \RuntimeException implements SourceExceptionInterface
{
    /**
     * @final
     */
    public const CODE_STREAM_WRITE = 0x01;

    /**
     * @final
     */
    public const CODE_STREAM_REWIND = 0x02;

    protected const CODE_LAST = self::CODE_STREAM_REWIND;

    final public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function fromStreamWriteOperation(string $stream): self
    {
        $message = \sprintf('Can not write content data into "%s" stream', $stream);

        return new static($message, self::CODE_STREAM_WRITE);
    }

    public static function fromStreamRewindOperation(string $stream): self
    {
        $message = \sprintf('Can not rewind "%s" stream', $stream);

        return new static($message, self::CODE_STREAM_REWIND);
    }
}
