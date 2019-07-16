<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io\File;

use Phplrt\Io\Exception\NotFoundException;
use Phplrt\Io\Exception\NotReadableException;

/**
 * Class Physical
 */
class Physical extends AbstractFile
{
    /**
     * @var string|null
     */
    protected $hash;

    /**
     * Physical constructor.
     *
     * @param string $pathname
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public function __construct(string $pathname)
    {
        $this->assertExists($pathname);

        parent::__construct(\realpath($pathname));
    }

    /**
     * @param string $name
     * @throws NotFoundException
     */
    private function assertExists(string $name): void
    {
        if (! \is_file($name)) {
            $error = 'File "%s" not found';
            throw new NotFoundException(\sprintf($error, $name));
        }
    }

    /**
     * @param string $name
     * @throws NotReadableException
     */
    private function assertReadable(string $name): void
    {
        if (! \is_readable($name)) {
            $error = 'Can not read the file "%s": Permission denied';
            throw new NotReadableException(\sprintf($error, \realpath($name)));
        }
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        if ($this->hash === null) {
            $this->hash = \sha1($this->getPathname() . ':' . \filemtime($this->getPathname()));
        }

        return $this->hash;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        $this->assertReadable($this->getPathname());

        return \file_get_contents($this->getPathname());
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        $fp = \fopen($this->getPathname(), 'rb+');

        \flock($fp, \LOCK_SH);

        return $fp;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function __sleep(): array
    {
        return \array_merge(parent::__sleep(), [
            'hash',
        ]);
    }
}
