<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Io\Readable;
use Phplrt\Exception\MutableException\MutableFileInterface;
use Phplrt\Exception\MutableException\MutableCodeInterface;
use Phplrt\Exception\MutableException\MutableMessageInterface;
use Phplrt\Exception\MutableException\MutablePositionInterface;

/**
 * Interface MutableExceptionInterface
 */
interface MutableExceptionInterface extends
    MutableCodeInterface,
    MutableFileInterface,
    MutableMessageInterface,
    MutablePositionInterface
{
    /**
     * @param Readable|string $file
     * @param int $offsetOrLine
     * @param int|null $column
     * @return MutableExceptionInterface|$this
     */
    public function throwsIn($file, int $offsetOrLine = 0, int $column = null): self;

    /**
     * @param \Throwable $e
     * @return MutableExceptionInterface|$this
     */
    public function throwsFrom(\Throwable $e): self;
}
