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
use Phplrt\Position\Position;
use Phplrt\Position\PositionInterface;

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
     * @param Readable|string $file
     * @param int $offsetOrLine
     * @param int|null $column
     * @return MutableExceptionInterface|$this
     */
    public function throwsIn($file, int $offsetOrLine = 0, int $column = null): MutableExceptionInterface
    {
        \assert(\is_string($file) || $file instanceof Readable);

        [$line, $column] = $this->resolveLineAndColumn($file, $offsetOrLine, $column);

        return $this
            ->withFile($this->resolveFilename($file))
            ->withLine($line)
            ->withColumn($column);
    }

    /**
     * @param Readable|string $file
     * @return string
     */
    private function resolveFilename($file): string
    {
        return $file instanceof Readable ? $file->getPathname() : $file;
    }

    /**
     * @param Readable|string $file
     * @param int $offset
     * @return PositionInterface
     */
    private function resolvePosition($file, int $offset): PositionInterface
    {
        if ($file instanceof Readable) {
            return $file->getPosition($offset);
        }

        return Position::fromOffset(\file_get_contents($file), $offset);
    }

    /**
     * @param Readable|string $file
     * @param int $offsetOrLine
     * @param int|null $column
     * @return int[]
     */
    private function resolveLineAndColumn($file, int $offsetOrLine = 0, int $column = null): array
    {
        if ($column === null) {
            $position = $this->resolvePosition($file, $offsetOrLine);

            return [$position->getLine(), $position->getColumn()];
        }

        return [$offsetOrLine, $column];
    }
}
