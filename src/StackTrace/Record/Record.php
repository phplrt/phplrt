<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\StackTrace\Record;

use Phplrt\Position\Position;
use Phplrt\Position\PositionInterface;

/**
 * Class Record
 */
class Record implements RecordInterface
{
    /**
     * @var string
     */
    protected const FORMAT = '#%d %s(%s): %s';

    /**
     * @var string
     */
    private $file;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var PositionInterface|null
     */
    private $position;

    /**
     * Record constructor.
     *
     * @param string $pathname
     * @param int $offset
     */
    public function __construct(string $pathname, int $offset)
    {
        $this->file   = $pathname;
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getColumn(): int
    {
        return $this->getPosition()->getColumn();
    }

    /**
     * @return PositionInterface
     */
    protected function getPosition(): PositionInterface
    {
        if ($this->position === null) {
            $this->position = Position::fromOffset($this->read($this->file), $this->offset);
        }

        return $this->position;
    }

    /**
     * @param string $pathname
     * @return resource|string
     */
    private function read(string $pathname)
    {
        if (\is_file($pathname) && \is_readable($pathname)) {
            return \fopen($pathname, 'rb+');
        }

        return '';
    }

    /**
     * @param int $i
     * @param \Closure $suffix
     * @return string
     */
    public function render(int $i, \Closure $suffix): string
    {
        return \sprintf(static::FORMAT, $i, $this->getFile(), $this->getLine(), $suffix($this));
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return int
     */
    public function getLine(): int
    {
        return $this->getPosition()->getLine();
    }
}
