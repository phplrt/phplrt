<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Source\File;
use Phplrt\Position\Position;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Contracts\Exception\MutableSourceExceptionInterface;
use Phplrt\Contracts\Source\Exception\NotReadableExceptionInterface;

/**
 * Class SourceException
 */
class SourceException extends \RuntimeException implements MutableSourceExceptionInterface
{
    /**
     * @var int|null
     */
    private $column;

    /**
     * @var int|null
     */
    private $offset;

    /**
     * @var string|ReadableInterface
     */
    private $readable;

    /**
     * @param \Throwable $e
     * @return SourceException
     */
    public static function from(\Throwable $e): self
    {
        $instance = new static($e->getMessage(), $e->getLine(), $e->getPrevious());
        $instance->file = $e->getFile();
        $instance->line = $e->getLine();

        if ($e instanceof self) {
            $instance->readable = $e->readable;
            $instance->column = $e->column;
            $instance->offset = $e->offset;
        }

        return $instance;
    }

    /**
     * @param ReadableInterface $readable
     * @param int $offset
     * @return MutableSourceExceptionInterface
     * @throws NotReadableExceptionInterface
     */
    public function throwsIn(ReadableInterface $readable, int $offset): MutableSourceExceptionInterface
    {
        $this->setReadable($readable);
        $this->setPosition(Position::fromOffset($readable, $offset));

        return $this;
    }

    /**
     * @param ReadableInterface $readable
     * @return void
     */
    private function setReadable(ReadableInterface $readable): void
    {
        $this->readable = $readable;

        if ($this->readable instanceof FileInterface) {
            $this->file = $this->readable->getPathName();
        }
    }

    /**
     * @param Position $position
     * @return void
     */
    private function setPosition(Position $position): void
    {
        $this->offset = $position->getOffset();
        $this->line = $position->getLine();
        $this->column = $position->getColumn();
    }

    /**
     * @param ReadableInterface $readable
     * @param int $line
     * @param int $column
     * @return MutableSourceExceptionInterface
     * @throws NotReadableExceptionInterface
     */
    public function throwsAt(ReadableInterface $readable, int $line, int $column = Position::MIN_COLUMN): MutableSourceExceptionInterface
    {
        $this->setReadable($readable);
        $this->setPosition(Position::fromPosition($readable, $line, $column));

        return $this;
    }

    /**
     * @return int
     * @throws NotReadableException
     * @throws NotReadableExceptionInterface
     */
    public function getColumn(): int
    {
        if ($this->column === null) {
            $column = $this->column ?? Position::MIN_COLUMN;

            $this->column = $this->offset === null
                ? Position::fromPosition($this->getSource(), $this->getLine(), $column)->getColumn()
                : Position::fromOffset($this->getSource(), $this->getOffset());
        }

        return $this->column;
    }

    /**
     * @return ReadableInterface
     * @throws NotReadableException
     */
    public function getSource(): ReadableInterface
    {
        if (! $this->readable) {
            $this->readable = File::fromPathName($this->getFile());
        }

        return $this->readable;
    }

    /**
     * @return int
     * @throws NotReadableException
     * @throws NotReadableExceptionInterface
     */
    public function getOffset(): int
    {
        if ($this->offset === null) {
            $column = $this->column ?? Position::MIN_COLUMN;

            $this->offset = Position::fromPosition($this->getSource(), $this->getLine(), $column)->getOffset();
        }

        return $this->offset;
    }
}
