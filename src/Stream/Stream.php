<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Stream;

use Phplrt\Exception\Wrapper;
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
     * @var Wrapper
     */
    private $expr;

    /**
     * Stream constructor.
     *
     * @param $resource
     */
    public function __construct($resource)
    {
        \assert($this->assertIsResource($resource), 'Assertion "' . \gettype($resource) . '" is resource');

        $this->resource = $resource;
        $this->expr = new Wrapper(\RuntimeException::class);
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
     * {@inheritDoc}
     * @throws \ErrorException
     */
    public function lock(int $mode = \LOCK_SH): void
    {
        $this->expr->wrap(function () use ($mode) {
            \flock($this->resource, $mode);
        });
    }

    /**
     * {@inheritDoc}
     * @throws \ErrorException
     */
    public function unlock(): void
    {
        $this->expr->wrap(function () {
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

        return $this->expr->wrap(function () {
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

        $this->expr->wrap(function () {
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

        return $this->expr->wrap(function (): int {
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

        return $this->expr->wrap(function () use ($string): int {
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

        return $this->expr->wrap(function () use ($length) {
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
