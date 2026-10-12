<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * A stream cannot be opened once again with the very same data, so nothing
 * identifies it once it is written down: it has no URI, or the URI it has
 * stands for the data of the process it has been opened in.
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

    /**
     * @param non-empty-string $uri
     */
    public static function becauseStreamBelongsToProcess(string $uri, ?\Throwable $prev = null): self
    {
        $message = 'The stream "%s" holds the data of the process it has been opened in '
            . 'and therefore cannot be serialized';

        return new self(\sprintf($message, $uri), previous: $prev);
    }
}
