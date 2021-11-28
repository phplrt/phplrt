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
     * @var non-empty-string
     */
    final protected const HASH_ALGORITHM = 'crc32';

    /**
     * @var non-empty-string|null
     */
    protected ?string $hash = null;

    /**
     * @param StreamReaderInterface $stream
     * @param ContentReaderInterface $content
     */
    public function __construct(
        private readonly StreamReaderInterface $stream,
        private readonly ContentReaderInterface $content,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function free(): void
    {
        $this->hash = null;

        if ($this->stream instanceof MemoizableInterface) {
            $this->stream->free();
        }

        if ($this->content instanceof MemoizableInterface) {
            $this->content->free();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getStream()
    {
        return $this->stream->getStream();
    }

    /**
     * {@inheritDoc}
     */
    public function getContents(): string
    {
        return $this->content->getContents();
    }

    /**
     * @param non-empty-string $algo
     * @param bool $binary
     * @return non-empty-string
     *
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress PropertyTypeCoercion
     */
    public function getHash(string $algo = self::HASH_ALGORITHM, bool $binary = false): string
    {
        return $this->hash ??= \hash($algo, $this->getContents(), $binary);
    }
}
