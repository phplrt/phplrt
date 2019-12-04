<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\File;

use Phplrt\Contracts\Source\FileInterface;

/**
 * Class VirtualStream
 */
class VirtualStream extends Stream implements FileInterface
{
    /**
     * @var string
     */
    private $pathname;

    /**
     * VirtualStream constructor.
     *
     * @param string $pathname
     * @param $stream
     */
    public function __construct(string $pathname, $stream)
    {
        $this->pathname = $pathname;

        parent::__construct($stream);
    }

    /**
     * @return string
     */
    public function getPathname(): string
    {
        return $this->pathname;
    }
}
