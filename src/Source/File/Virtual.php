<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Source\File;

use Phplrt\Contracts\Source\Exception\NotReadableExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;

/**
 * @internal A FileInterface internal implementation
 */
class Virtual extends Readable implements FileInterface
{
    /**
     * @var string
     */
    private $pathName;

    /**
     * @var string
     */
    private $content;

    /**
     * Virtual constructor.
     *
     * @param string $pathName
     * @param string $content
     */
    public function __construct(string $pathName, string $content)
    {
        $this->pathName = $pathName;
        $this->content  = $content;
    }

    /**
     * @return resource
     * @throws NotReadableExceptionInterface
     */
    public function getStream()
    {
        return (new Source($this->getContents()))->getStream();
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getPathName(): string
    {
        return $this->pathName;
    }

    /**
     * @return string
     */
    protected function calculateHash(): string
    {
        return \hash(static::HASH_ALGORITHM, $this->content);
    }
}
