<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io\File;

use Phplrt\Io\Stream;
use Phplrt\Io\StreamInterface;

/**
 * Class Virtual
 */
class Virtual extends AbstractFile
{
    /**
     * @var string A default file name which created from sources
     */
    public const DEFAULT_FILE_NAME = 'php://input';

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string|null
     */
    protected $hash;

    /**
     * Virtual constructor.
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
     * @param array $options
     * @return StreamInterface
     */
    public function getStream(array $options = []): StreamInterface
    {
        return Stream::fromContent($this->getContents());
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
    public function getStreamContents(bool $exclusive = false)
    {
        return Stream::fromContent($this->getContents())->getResource();
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
