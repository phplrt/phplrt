<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\File;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;

/**
 * Class Physical
 */
class Physical extends Readable implements FileInterface
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
     * Physical constructor.
     *
     * @param string $pathname
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public function __construct(string $pathname)
    {
        $this->assertValid($pathname);

        $this->pathname = \realpath($pathname);
    }

    /**
     * @param string $pathname
     * @return void
     * @throws NotFoundException
     * @throws NotReadableException
     */
    private function assertValid(string $pathname): void
    {
        $this->assertExists($pathname);
        $this->assertIsReadable($pathname);
    }

    /**
     * @param string $pathname
     * @throws NotFoundException
     */
    private function assertExists(string $pathname): void
    {
        if (! $this->exists($pathname)) {
            $message = \sprintf(self::ERROR_NOT_FOUND, $pathname);

            throw new NotFoundException($message);
        }
    }

    /**
     * @param string $pathname
     * @return bool
     */
    private function exists(string $pathname): bool
    {
        return \is_file($pathname);
    }

    /**
     * @param string $pathname
     * @return void
     * @throws NotReadableException
     */
    private function assertIsReadable(string $pathname): void
    {
        if (! $this->isReadable($pathname)) {
            $message = \sprintf(self::ERROR_NOT_READABLE, \realpath($pathname));

            throw new NotReadableException($message);
        }
    }

    /**
     * @param string $pathname
     * @return bool
     */
    private function isReadable(string $pathname): bool
    {
        return \is_readable($pathname);
    }

    /**
     * {@inheritDoc}
     */
    public function getStream()
    {
        $this->assertValid($this->getPathname());

        return \fopen($this->getPathname(), 'rb');
    }

    /**
     * {@inheritDoc}
     */
    public function getPathname(): string
    {
        return $this->pathname;
    }

    /**
     * {@inheritDoc}
     */
    final public function getHash(): string
    {
        if ($this->hash === null) {
            $this->hash = \hash_file('crc32', $this->getPathname());
        }

        return $this->hash;
    }

    /**
     * @return string
     * @throws NotReadableException
     */
    protected function read(): string
    {
        $this->assertValid($this->getPathname());

        return \file_get_contents($this->getPathname());
    }
}
