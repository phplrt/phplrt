<?php

declare(strict_types=1);

namespace Phplrt\Source\Stream;

use Phplrt\Contracts\Source\Stream\ReadableStreamInterface;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Exception\NotReadableException;

/**
 * An arbitrary cursor over a resource stream
 *
 * @internal do not work with this implementation directly, use the interface instead
 */
abstract class ResourceStream implements ReadableStreamInterface
{
    /**
     * @throws NotCreatableException When the given value is not a resource stream
     */
    public function __construct(
        /**
         * @var resource
         */
        protected readonly mixed $stream,
        /**
         * Whether the resource stream is closed along with this object.
         */
        private readonly bool $autoclose = false,
    ) {
        // Invariant against the callers not covered by static analysis.
        if (!\is_resource($stream)) {
            throw NotCreatableException::becauseSourceIsInvalid($stream);
        }

        if (\get_resource_type($stream) !== 'stream') {
            throw NotCreatableException::becauseSourceIs('non-stream resource');
        }
    }

    /**
     * Reads the given number of bytes out of the resource at the position it
     * currently is at.
     *
     * @param int<1, max> $bytes
     * @throws NotReadableException When the stream cannot be read
     */
    final protected function fetch(int $bytes): string
    {
        \error_clear_last();

        $result = @\fread($this->stream, $bytes);

        if ($result === false) {
            throw NotReadableException::becauseInternalErrorOccurs(\error_get_last());
        }

        return $result;
    }

    public function __destruct()
    {
        if ($this->autoclose && \is_resource($this->stream)) {
            \fclose($this->stream);
        }
    }
}
