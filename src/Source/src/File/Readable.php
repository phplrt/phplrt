<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\File;

use Phplrt\Source\MemoizableInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Class Readable
 */
abstract class Readable implements ReadableInterface, MemoizableInterface
{
    /**
     * @var string|null
     */
    private ?string $content = null;

    /**
     * @var string|null
     */
    protected ?string $hash = null;

    /**
     * @return string
     */
    abstract protected function read(): string;

    /**
     * {@inheritDoc}
     */
    public function getContents(): string
    {
        if ($this->content === null) {
            $this->content = $this->read();
        }

        return $this->content;
    }

    /**
     * {@inheritDoc}
     */
    public function getHash(): string
    {
        if ($this->hash === null) {
            $this->hash = \hash('crc32', $this->getContents());
        }

        return $this->hash;
    }

    /**
     * {@inheritDoc}
     */
    public function refresh(): void
    {
        $this->hash = $this->content = null;
    }
}
