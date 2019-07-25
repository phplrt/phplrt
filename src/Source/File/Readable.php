<?php
/**
 * This file is part of source package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Source\File;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Class Readable
 */
abstract class Readable implements ReadableInterface, MemoizableInterface
{
    /**
     * @var string
     */
    protected const HASH_ALGORITHM = 'sha1';

    /**
     * @var string|null
     */
    private $hash;

    /**
     * @return string
     */
    abstract protected function calculateHash(): string;

    /**
     * @return string
     */
    public function getHash(): string
    {
        if ($this->hash === null) {
            return $this->calculateHash();
        }

        return $this->hash;
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
        $this->hash = null;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        if ($this instanceof FileInterface) {
            return $this->getPathName();
        }

        return 'php://memory';
    }
}
