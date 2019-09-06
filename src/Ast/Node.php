<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

use Phplrt\Contracts\Ast\NodeInterface;

/**
 * Class Node
 */
abstract class Node implements NodeInterface, \JsonSerializable
{
    /**
     * @var int
     */
    public $offset;

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'offset'     => $this->getOffset(),
            'children'   => \iterator_to_array($this->getIterator(), false),
        ];
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return (int)$this->offset;
    }

    /**
     * @param int $offset
     * @return Node|$this
     */
    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }
}
