<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io;

use Phplrt\Exception\ErrorWrapper;
use Phplrt\Io\Exception\StreamException;

/**
 * Class Stream
 */
class Stream implements StreamInterface, StreamFactoryInterface
{
    use StreamFactoryTrait;

    /**
     * @var string
     */
    private const CLOSED_RESOURCE_TYPE = 'unknown type';

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
        //
        // Determine that $resource is valid "resource" or "closed resource" type
        //
        \assert(\is_resource($resource) || \gettype($resource) === self::CLOSED_RESOURCE_TYPE);

        $this->resource = $resource;
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
        throw new \LogicException(\sprintf('%s not implemented yet', __METHOD__));
    }

    /**
     * {@inheritDoc}
     */
    public function lock(): void
    {
        ErrorWrapper::wrap(function () {
            \flock($this->resource, \LOCK_SH);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function unlock(): void
    {
        ErrorWrapper::wrap(function () {
            \flock($this->resource, \LOCK_UN);
        });
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
    public function getContents(): string
    {
        $this->assertResourceIsReadable();

        return ErrorWrapper::wrap(function () {
            return \stream_get_contents($this->resource);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        if (! $this->resource) {
            return;
        }

        ErrorWrapper::wrap(function () {
            $resource = $this->detach();

            \fclose($resource);
        });
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

        return ErrorWrapper::wrap(function (): int {
            return \ftell($this->resource);
        });
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
    private function assertResourceIsWritable(): void
    {
        if (! $this->isWritable()) {
            throw new StreamException('Stream is not writable');
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
    public function write($string): int
    {
        \assert(\is_string($string));

        $this->assertResourceIsAvailable();
        $this->assertResourceIsWritable();

        return ErrorWrapper::wrap(function () use ($string): int {
            return \fwrite($this->resource, $string);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function read($length): string
    {
        \assert(\is_int($length));

        $this->assertResourceIsAvailable();
        $this->assertResourceIsReadable();

        return ErrorWrapper::wrap(function () use ($length) {
            return \fread($this->resource, $length);
        });
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
}
