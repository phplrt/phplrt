<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Source\File;

use Phplrt\Source\File as Facade;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;

/**
 * @internal A FileInterface internal implementation
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
    private const ERROR_NOT_READABLE = 'Can not read the file "%s". Permission denied';

    /**
     * @var string
     */
    private $pathName;

    /**
     * @var string|null
     */
    private $content;

    /**
     * File constructor.
     *
     * @param string $pathName
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public function __construct(string $pathName)
    {
        $this->assertExists($pathName);
        $this->assertIsReadable($pathName);

        $this->pathName = \realpath($pathName);
    }

    /**
     * @param string $pathName
     * @throws NotFoundException
     */
    private function assertExists(string $pathName): void
    {
        if (! Facade::exists($pathName)) {
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
        if (! Facade::isReadable($pathName)) {
            $message = \sprintf(self::ERROR_NOT_READABLE, \realpath($pathName));
            throw new NotReadableException($message);
        }
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return \fopen($this->getPathName(), 'rb');
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return \file_get_contents($this->getPathName());
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
        $this->content = null;

        parent::refresh();
    }

    /**
     * @return string
     */
    protected function calculateHash(): string
    {
        return \hash_file(static::HASH_ALGORITHM, $this->getPathName());
    }
}
