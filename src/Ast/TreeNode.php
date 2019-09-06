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
 * A simple AST node implementation.
 */
class TreeNode extends Node
{
    use ChildNodesTrait;

    /**
     * @return \Traversable|NodeInterface[]
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->getChildNodeNames() as $name) {
            if (isset($this->$name)) {
                yield $name => $this->$name;
            } else {
                yield $name => static::$$name;
            }
        }
    }
}
