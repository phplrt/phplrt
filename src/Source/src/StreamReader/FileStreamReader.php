<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\StreamReader;

use Phplrt\Source\Exception\NotReadableException;

/**
 * Class FileStreamReader
 */
class FileStreamReader implements StreamReaderInterface
{
    /**
     * @var string
     */
    private const ERROR_NOT_READABLE = 'An error occurred while trying to open the file "%s" for reading';

    /**
     * @var string
     */
    private string $pathname;

    /**
     * FileStreamReader constructor.
     *
     * @param string $pathname
     */
    public function __construct(string $pathname)
    {
        $this->pathname = $pathname;
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        $stream = @\fopen($this->pathname, 'rb');

        if (! \is_resource($stream)) {
            throw new NotReadableException(\sprintf(self::ERROR_NOT_READABLE, $this->pathname));
        }

        return $stream;
    }
}
