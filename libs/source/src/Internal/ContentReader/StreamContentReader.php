<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\Internal\ContentReader;

use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\Internal\ContentReaderInterface;
use Phplrt\Source\Internal\Util;
use Phplrt\Source\MemoizableInterface;

/**
 * @internal StreamContentReader is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\source
 */
final class StreamContentReader implements ContentReaderInterface, MemoizableInterface
{
    /**
     * @var string
     */
    private const METADATA_KEY_SEEKABLE = 'seekable';

    /**
     * @var string
     */
    private const ERROR_NOT_SEEKABLE =
        'Impossible to read a stream from the beginning for non-seekable stream';

    /**
     * @var resource
     */
    private $stream;

    /**
     * @var string|null
     */
    private ?string $content = null;

    /**
     * StreamContentReader constructor.
     *
     * @param resource $stream
     */
    public function __construct($stream)
    {
        \assert(Util::isNonClosedStream($stream));

        $this->stream = $stream;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        $this->rewind();

        return \stream_get_contents($this->stream);
    }

    /**
     * @return void
     */
    private function rewind(): void
    {
        // In the case that the cursor is not at the beginning.
        if (\ftell($this->stream) !== 0) {
            // If at the same time the stream is not a seekable,
            // then we cannot reset its cursor.
            if (! $this->isSeekable()) {
                throw new NotAccessibleException(self::ERROR_NOT_SEEKABLE);
            }

            \rewind($this->stream);
        }
    }

    /**
     * @return bool
     */
    private function isSeekable(): bool
    {
        return (bool)(\stream_get_meta_data($this->stream)[self::METADATA_KEY_SEEKABLE] ?? false);
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
        $this->content = null;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return Util::serialize($this->stream);
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->stream = Util::unserialize($data);
    }
}
