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
}
