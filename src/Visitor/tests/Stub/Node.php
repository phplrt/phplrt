<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor\Tests\Stub;

use Phplrt\Contracts\Ast\NodeInterface;

/**
 * Class Node
 */
class Node implements NodeInterface
{
    /**
     * @var array|NodeInterface[]
     */
    public $children;

    /**
     * @var int
     */
    private $id;

    /**
     * Node constructor.
     *
     * @param int $id
     * @param array $children
     */
    public function __construct(int $id, array $children = [])
    {
        $this->children = $children;
        $this->id       = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \Traversable|NodeInterface[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(['children' => $this->children]);
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return 0;
    }
}
