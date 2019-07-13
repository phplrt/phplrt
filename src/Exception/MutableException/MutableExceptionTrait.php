<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception\MutableException;

use Phplrt\Contracts\Exception\MutableExceptionInterface;
use Phplrt\Contracts\Io\Readable;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Io\File;

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
        \assert($file instanceof Readable || \is_string($file));

        $file = $this->resolveFile($file);

        if (\property_exists($this, 'readable')) {
            $this->readable = $file;
        }

        [$line, $column] = $this->resolveLineAndColumn($file, $offsetOrLine, $column);

        return $this
            ->withFile($this->resolveFilename($file))
            ->withLine($line)
            ->withColumn($column);
    }

    /**
     * @param string|Readable $file
     * @return \Phplrt\Contracts\Io\Readable
     */
    private function resolveFile($file): Readable
    {
        return \is_string($file) && ! \is_file($file)
            ? File::fromSources($file)
            : File::new($file);
    }

    /**
     * @param Readable $file
     * @param int $offsetOrLine
     * @param int|null $column
     * @return int[]
     */
    private function resolveLineAndColumn(Readable $file, int $offsetOrLine = 0, int $column = null): array
    {
        if ($column === null) {
            $position = $file->getPosition($offsetOrLine);

            return [$position->getLine(), $position->getColumn()];
        }

        return [$offsetOrLine, $column];
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
     * @param \Throwable $e
     * @return MutableExceptionInterface
     */
    public function throwsFrom(\Throwable $e): MutableExceptionInterface
    {
        $this
            ->withMessage($e->getMessage())
            ->withCode($e->getCode())
            ->withFile($e->getFile())
            ->withLine($e->getLine());

        if ($e instanceof PositionInterface) {
            $this->withColumn($e->getColumn());
        }

        return $this;
    }
}
