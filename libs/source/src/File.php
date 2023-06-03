<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\ContentReader\ContentReaderInterface;
use Phplrt\Source\ContentReader\FileContentReader;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\StreamReader\FileStreamReader;
use Phplrt\Source\StreamReader\StreamReaderInterface;

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
    private string $pathname;

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
        $this->pathname = \realpath($pathname);

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
        if (! \is_file($pathname)) {
            $message = \sprintf(self::ERROR_NOT_FOUND, $pathname);

            throw new NotFoundException($message);
        }

        if (! \is_readable($pathname)) {
            $message = \sprintf(self::ERROR_NOT_READABLE, \realpath($pathname));

            throw new NotReadableException($message);
        }
    }

    public function getPathname(): string
    {
        return $this->pathname;
    }

    final public function getHash(): string
    {
        return \hash_file(static::HASH_ALGORITHM, $this->pathname);
    }
}
