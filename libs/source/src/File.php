<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Internal\ContentReader\FileContentReader;
use Phplrt\Source\Internal\ContentReaderInterface;
use Phplrt\Source\Internal\StreamReader\FileStreamReader;
use Phplrt\Source\Internal\StreamReaderInterface;

class File extends Readable implements FileInterface
{
    /**
     * @var string
     */
    private const ERROR_NOT_FOUND = 'File "%s" not found';

    /**
     * @var string
     */
    private const ERROR_NOT_READABLE = 'Can not read the file "%s"';

    /**
     * @var string
     */
    private readonly string $pathname;

    /**
     * @param string $pathname
     * @param StreamReaderInterface|null $stream
     * @param ContentReaderInterface|null $content
     */
    public function __construct(
        string $pathname,
        StreamReaderInterface $stream = null,
        ContentReaderInterface $content = null
    ) {
        $this->pathname = \realpath($pathname) ?: $pathname;

        $stream ??= new FileStreamReader($pathname);
        $content ??= new FileContentReader($pathname);

        parent::__construct($stream, $content);
    }

    /**
     * @param string $pathname
     * @return void
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public static function assertValidPathname(string $pathname): void
    {
        assert(\is_file($pathname), new NotFoundException(
            \sprintf(self::ERROR_NOT_FOUND, \realpath($pathname) ?: $pathname)
        ));

        assert(\is_readable($pathname), new NotReadableException(
            \sprintf(self::ERROR_NOT_READABLE, \realpath($pathname) ?: $pathname)
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getPathname(): string
    {
        return $this->pathname;
    }

    /**
     * @param string $algo
     * @param bool $binary
     * @return non-empty-string
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress PropertyTypeCoercion
     */
    final public function getHash(string $algo = self::HASH_ALGORITHM, bool $binary = false): string
    {
        return $this->hash ??= \hash_file($algo, $this->pathname, $binary);
    }
}
