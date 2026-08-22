<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * An error in the code working with a source rather than a failure of the
 * source itself.
 */
class LogicException extends \LogicException implements SourceExceptionInterface
{
    final public const int CODE_STREAM_URI = 0x01;
    final public const int CODE_STREAM_SEEK = 0x02;

    /**
     * @param non-empty-string $stream
     */
    public static function becauseStreamHasNoUri(string $stream, ?\Throwable $prev = null): self
    {
        $message = 'The stream "%s" has no URI and therefore cannot be serialized';

        return new self(\sprintf($message, $stream), self::CODE_STREAM_URI, $prev);
    }

    /**
     * @param non-empty-string $stream
     */
    public static function becauseStreamIsNotSeekable(string $stream, ?\Throwable $prev = null): self
    {
        $message = 'The stream "%s" does not support offset (seek/rewind) changes';

        return new self(\sprintf($message, $stream), self::CODE_STREAM_SEEK, $prev);
    }
}
