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
 * Trait MutableExceptionTrait
 *
 * @mixin MutableExceptionInterface
 */
trait MutableExceptionTrait
{
    use MutableFileTrait;
    use MutableCodeTrait;
    use MutableMessageTrait;
    use MutablePositionTrait;

    /**
     * @param Readable $file
     * @param int $offsetOrLine
     * @param int|null $column
     * @return MutableExceptionInterface|$this
     */
    public function throwsIn(Readable $file, int $offsetOrLine = 0, int $column = null): MutableExceptionInterface
    {
        [$line, $column] = $this->resolvePosition($file, $offsetOrLine, $column);

        return $this->withFile($file->getPathname())->withLine($line)->withColumn($column);
    }

    /**
     * @param Readable $file
     * @param int $offsetOrLine
     * @param int|null $column
     * @return int[]
     */
    private function resolvePosition(Readable $file, int $offsetOrLine = 0, int $column = null): array
    {
        if ($column === null) {
            $position = $file->getPosition($offsetOrLine);

            return [$position->getLine(), $position->getColumn()];
        }

        return [$offsetOrLine, $column];
    }
}
