<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Internal\ContentReaderInterface;
use Phplrt\Source\Internal\StreamReaderInterface;

class Readable implements ReadableInterface, MemoizableInterface
{
    use FactoryTrait;

    /**
     * @var string
     */
    protected const HASH_ALGORITHM = 'crc32';

    /**
     * @var StreamReaderInterface
     */
    private StreamReaderInterface $stream;

    /**
     * @var ContentReaderInterface
     */
    private ContentReaderInterface $content;

    /**
     * @var string|null
     */
    protected ?string $hash = null;

    /**
     * @param StreamReaderInterface $stream
     * @param ContentReaderInterface $content
     */
    public function __construct(StreamReaderInterface $stream, ContentReaderInterface $content)
    {
        $this->stream = $stream;
        $this->content = $content;
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
        $this->hash = null;

        if ($this->stream instanceof MemoizableInterface) {
            $this->stream->refresh();
        }

        if ($this->content instanceof MemoizableInterface) {
            $this->content->refresh();
        }
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream->getStream();
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return $this->content->getContents();
    }

    /**
     * @param string $algo
     * @param bool $binary
     * @return non-empty-string
     */
    public function getHash(string $algo = self::HASH_ALGORITHM, bool $binary = false): string
    {
        return $this->hash ??= \hash($algo, $this->getContents(), $binary);
    }
}
