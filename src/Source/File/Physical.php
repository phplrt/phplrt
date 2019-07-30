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
use Phplrt\Source\Exception\NotAccessibleException;

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
    private $pathName;

    /**
     * File constructor.
     *
     * @param string $pathName
     */
    public function __construct(string $pathName)
    {
        $this->pathName = $pathName;
    }

    /**
     * @return resource
     * @throws NotAccessibleException
     */
    public function getStream()
    {
        $this->assertExists($this->getPathName());
        $this->assertIsReadable($this->getPathName());

        return \fopen($this->getPathName(), 'rb');
    }

    /**
     * @return string
     * @throws NotFoundException
     * @throws NotReadableException
     */
    protected function read(): string
    {
        $this->assertExists($this->getPathName());
        $this->assertIsReadable($this->getPathName());

        return \file_get_contents($this->getPathName());
    }

    /**
     * @param string $pathName
     * @return bool
     */
    private function exists(string $pathName): bool
    {
        return \is_file($pathName);
    }

    /**
     * @param string $pathName
     * @return bool
     */
    private function isReadable(string $pathName): bool
    {
        return \is_readable($pathName);
    }

    /**
     * @param string $pathName
     * @throws NotFoundException
     */
    private function assertExists(string $pathName): void
    {
        if (! $this->exists($pathName)) {
            $message = \sprintf(self::ERROR_NOT_FOUND, $pathName);

            throw new NotFoundException($message);
        }
    }

    /**
     * @return string
     */
    public function getPathName(): string
    {
        return $this->pathName;
    }

    /**
     * @param string $pathName
     * @return void
     * @throws NotReadableException
     */
    private function assertIsReadable(string $pathName): void
    {
        if (! $this->isReadable($pathName)) {
            $message = \sprintf(self::ERROR_NOT_READABLE, \realpath($pathName));

            throw new NotReadableException($message);
        }
    }
}
