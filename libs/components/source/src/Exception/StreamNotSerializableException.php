<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * A stream has no URI, so nothing identifies it once it is written down.
 */
final class StreamNotSerializableException extends LogicException
{
    /**
     * @param non-empty-string $stream
     */
    public static function becauseStreamHasNoUri(string $stream, ?\Throwable $prev = null): self
    {
        $message = 'The stream "%s" has no URI and therefore cannot be serialized';

        return new self(\sprintf($message, $stream), previous: $prev);
    }
}
