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
 * Class VirtualContent
 */
class VirtualContent extends Content implements FileInterface
{
    /**
     * @var string
     */
    private $pathName;

    /**
     * VirtualContent constructor.
     *
     * @param string $pathName
     * @param string $content
     */
    public function __construct(string $pathName, string $content)
    {
        parent::__construct($content);

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
