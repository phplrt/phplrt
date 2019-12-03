<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\File;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotAccessibleException;

/**
 * Class Content
 */
class Content implements ReadableInterface
{
    /**
     * @var string
     */
    private $content;

    /**
     * Content constructor.
     *
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return resource
     * @throws NotAccessibleException
     */
    public function getStream()
    {
        /** @var resource $memory */
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
     * @return string
     */
    public function getContents(): string
    {
        return $this->content;
    }
}
