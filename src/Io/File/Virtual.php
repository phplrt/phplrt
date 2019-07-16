<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io\File;

use Phplrt\Io\Exception\NotAccessibleException;

/**
 * Class Content
 */
class Virtual extends AbstractFile
{
    /**
     * @var string A default file name which created from sources
     */
    public const DEFAULT_FILE_NAME = 'php://memory';

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string|null
     */
    protected $hash;

    /**
     * Content constructor.
     *
     * @param string $content
     * @param string|null $name
     */
    public function __construct(string $content, string $name = null)
    {
        $this->content = $content;

        parent::__construct($name ?? self::DEFAULT_FILE_NAME);
    }

    /**
     * {@inheritDoc}
     */
    public function exists(): bool
    {
        return \is_file($this->getPathname());
    }

    /**
     * @return array
     */
    public function __sleep(): array
    {
        return \array_merge(parent::__sleep(), [
            'hash',
            'content',
        ]);
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        $memory = @\fopen('php://memory', 'rb+');

        if ($memory === false) {
            throw new NotAccessibleException('Can not open php://memory');
        }

        if (@\fwrite($memory, $this->getContents()) === false) {
            throw new NotAccessibleException('Can not write content data');
        }

        if (@\rewind($memory) === false) {
            throw new NotAccessibleException('Memory data is not rewindable');
        }

        return $memory;
    }

    /**
     * {@inheritDoc}
     */
    public function getContents(): string
    {
        return $this->content;
    }

    /**
     * {@inheritDoc}
     */
    public function getHash(): string
    {
        if ($this->hash === null) {
            $this->hash = \sha1($this->getPathname() . ':' . $this->content);
        }

        return $this->hash;
    }
}
