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
    private $content;

    /**
     * @return string
     */
    abstract protected function read(): string;

    /**
     * @return string
     */
    public function getContents(): string
    {
        if ($this->content === null) {
            $this->content = $this->read();
        }

        return $this->content;
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
        $this->content = null;
    }
}
