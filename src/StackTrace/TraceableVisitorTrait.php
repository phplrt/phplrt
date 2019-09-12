<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\StackTrace;

/**
 * Trait TraceableVisitorTrait
 */
trait TraceableVisitorTrait
{
    /**
     * @var \SplFileInfo
     */
    protected $file;

    /**
     * @return \SplFileInfo
     */
    public function getFile(): \SplFileInfo
    {
        \assert($this->file instanceof \SplFileInfo, 'File should be defined');

        return $this->file;
    }
}
