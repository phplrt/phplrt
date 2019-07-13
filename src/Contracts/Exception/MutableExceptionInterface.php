<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Exception;

use Phplrt\Contracts\Exception\MutableException\MutableCodeInterface;
use Phplrt\Contracts\Exception\MutableException\MutableFileInterface;
use Phplrt\Contracts\Exception\MutableException\MutableMessageInterface;
use Phplrt\Contracts\Exception\MutableException\MutablePositionInterface;
use Phplrt\Contracts\Io\Readable;

/**
 * Interface MutableExceptionInterface
 */
interface MutableExceptionInterface extends
    MutableCodeInterface,
    MutableFileInterface,
    MutableMessageInterface,
    MutablePositionInterface,
    ExternalExceptionInterface
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
