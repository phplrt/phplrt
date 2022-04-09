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

class Node implements NodeInterface
{
    /**
     * @var array|NodeInterface[]
     */
    public array $children;

    /**
     * @var int
     */
    private int $id;

    /**
     * @param int $id
     * @param array $children
     */
    public function __construct(int $id, array $children = [])
    {
        $this->id = $id;
        $this->children = $children;
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
