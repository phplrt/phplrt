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
    private $pathName;

    /**
     * VirtualStream constructor.
     *
     * @param string $pathName
     * @param $stream
     */
    public function __construct(string $pathName, $stream)
    {
        parent::__construct($stream);
        $this->pathName = $pathName;
    }

    /**
     * @return string
     */
    public function getPathName(): string
    {
        return $this->pathName;
    }
}
