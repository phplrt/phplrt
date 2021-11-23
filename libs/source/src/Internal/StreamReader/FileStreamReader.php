<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\Internal\StreamReader;

use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Internal\StreamReaderInterface;

/**
 * @internal FileStreamReader is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\source
 */
final class FileStreamReader implements StreamReaderInterface
{
    /**
     * @var string
     */
    private const ERROR_NOT_READABLE = 'An error occurred while trying to open the file "%s" for reading';

    /**
     * FileStreamReader constructor.
     *
     * @param string $pathname
     */
    public function __construct(
        private readonly string $pathname
    ) {
    }

    /**
     * @return resource
     */
    public function getStream(): mixed
    {
        $stream = @\fopen($this->pathname, 'rb');

        if (! \is_resource($stream)) {
            throw new NotReadableException(\sprintf(self::ERROR_NOT_READABLE, $this->pathname));
        }

        return $stream;
    }
}
