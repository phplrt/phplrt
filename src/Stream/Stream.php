<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Stream;

use Phplrt\Stream\Exception\StreamException;

/**
 * Class Stream
 */
class Stream implements StreamInterface
{
    /**
     * @var resource
     */
    private $resource;

    /**
     * Stream constructor.
     *
     * @param $resource
     */
    public function __construct($resource)
    {
        \assert($this->assertIsResource($resource), 'Assertion "' . \gettype($resource) . '" is resource');

        $this->resource = $resource;
    }

    /**
     * Determine that $resource is valid "resource" or "closed resource" type
     *
     * @param resource $resource
     * @return bool
     */
    private function assertIsResource($resource): bool
    {
        switch (true) {
            case \is_resource($resource):
                return true;

            // PHP 7.2 or greater
            case \version_compare(\PHP_VERSION, '7.2') >= 1:
                return \gettype($resource) === 'resource (closed)';

            // PHP 7.1 or lower
            default:
                return \gettype($resource) === 'unknown type';
        }
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritDoc}
     */
    public function readLine(): string
    {
        $this->assertResourceIsAvailable();
        $this->assertResourceIsReadable();

        return \fgets($this->resource) ?: '';
    }

    /**
     * @return void
     * @throws StreamException
     */
    private function assertResourceIsAvailable(): void
    {
        if (! $this->resource) {
            throw new StreamException('Stream was previously closed or detached');
        }
    }

    /**
     * @return void
     * @throws StreamException
     */
    private function assertResourceIsReadable(): void
    {
        if (! $this->isReadable()) {
            throw new StreamException('Stream is not readable');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isReadable(): bool
    {
        if (! $this->resource) {
            return false;
        }

        return $this->hasMode('r', '+');
    }

    /**
     * @param string ...$mode
     * @return bool
     */
    private function hasMode(string ...$mode): bool
    {
        $haystack = $this->getMetadata('mode');

        if (! \is_string($haystack)) {
            return false;
        }

        foreach ($mode as $needle) {
            if (\strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata($key = null)
    {
        if ($this->resource === null) {
            return null;
        }

        $metadata = \stream_get_meta_data($this->resource);

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }

    /**
     * {@inheritDoc}
     * @throws \ErrorException
     */
    public function lock(int $mode = \LOCK_SH): void
    {
        $status = @\flock($this->resource, $mode);

        if ($status === false) {
            throw new StreamException('Unable to lock stream resource');
        }
    }

    /**
     * {@inheritDoc}
     * @throws \ErrorException
     */
    public function unlock(): void
    {
        $status = @\flock($this->resource, \LOCK_UN);

        if ($status === false) {
            throw new StreamException('Unable to unlock stream resource');
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        if (! $this->isReadable()) {
            return '';
        }

        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (\RuntimeException $e) {
            return '';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isSeekable(): bool
    {
        return $this->getMetadata('seekable') === true;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * {@inheritDoc}
     */
    public function seek($offset, $whence = \SEEK_SET): void
    {
        $this->assertResourceIsAvailable();
        $this->assertResourceIsSeekable();

        $bytes = \fseek($this->resource, $offset, $whence);

        if ($bytes !== 0) {
            throw new StreamException('There was an internal error while stream seeking');
        }
    }

    /**
     * @return void
     * @throws StreamException
     */
    private function assertResourceIsSeekable(): void
    {
        if (! $this->isSeekable()) {
            throw new StreamException('Stream is not seekable');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getContents(): string
    {
        $this->assertResourceIsReadable();

        $result = \stream_get_contents($this->resource);

        if ($result === false) {
            throw new StreamException('Error while reading the stream');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        if (! $this->resource) {
            return;
        }

        $resource = $this->detach();

        if (@\fclose($resource) === false) {
            throw new StreamException('Unable to close stream resource');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        $stats = \fstat($this->resource);

        if ($stats !== false) {
            return $stats['size'];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function tell(): int
    {
        $this->assertResourceIsAvailable();

        $position = @\ftell($this->resource);

        if ($position === false) {
            throw new StreamException('Unable to read position of streamed resource');
        }

        return $position;
    }

    /**
     * {@inheritDoc}
     */
    public function eof(): bool
    {
        if (! $this->resource) {
            return true;
        }

        return \feof($this->resource);
    }

    /**
     * {@inheritDoc}
     */
    public function write($string): int
    {
        \assert(\is_string($string));

        $this->assertResourceIsAvailable();
        $this->assertResourceIsWritable();

        $status = @\fwrite($this->resource, $string);

        if ($status === false) {
            throw new StreamException('An internal error occurred while writing data into the stream');
        }

        return $status;
    }

    /**
     * @return void
     * @throws StreamException
     */
    private function assertResourceIsWritable(): void
    {
        if (! $this->isWritable()) {
            throw new StreamException('Stream is not writable');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable(): bool
    {
        if (! $this->resource) {
            return false;
        }

        return $this->hasMode('x', 'w', 'c', 'a', '+');
    }

    /**
     * {@inheritDoc}
     */
    public function read($length): string
    {
        \assert(\is_int($length), \vsprintf('Length should be an integer, but %s given', [
            \gettype($length),
        ]));

        \assert(\is_int($length));

        $this->assertResourceIsAvailable();
        $this->assertResourceIsReadable();

        $status = @\fread($this->resource, $length);

        if ($status === false) {
            throw new StreamException('An internal error occurred while reading data from the stream');
        }

        return $status;
    }
}
