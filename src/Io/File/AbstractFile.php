<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io\File;

use Phplrt\Io\Readable;
use Phplrt\Position\Position;
use Phplrt\Exception\ErrorWrapper;
use Phplrt\Position\PositionInterface;
use Phplrt\Io\Exception\NotReadableException;

/**
 * Class AbstractFile
 */
abstract class AbstractFile implements Readable
{
    /**
     * @var string
     */
    protected $name;

    /**
     * AbstractFile constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getPosition(int $offset): PositionInterface
    {
        return Position::fromOffset($this->getContents(), $offset);
    }

    /**
     * @return array|string[]
     */
    public function __sleep(): array
    {
        return [
            'name',
        ];
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        $content = \substr($this->getContents(), 0, 80);

        return [
            'name'    => $this->getPathname(),
            'hash'    => $this->getHash(),
            'content' => \str_replace("\n", '\n', $content . '...'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getPathname(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getPathname();
    }
}
