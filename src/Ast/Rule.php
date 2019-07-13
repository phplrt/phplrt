<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

use Phplrt\Contracts\Ast\LeafInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Ast\RuleInterface;

/**
 * Class Rule
 */
class Rule extends Node implements RuleInterface
{
    /**
     * @var array|iterable|\Traversable
     */
    private $children;

    /**
     * Rule constructor.
     *
     * @param string $name
     * @param array|NodeInterface[] $children
     * @param int $offset
     */
    public function __construct(string $name, array $children = [], int $offset = 0)
    {
        parent::__construct($name, $offset);

        $this->children = $children;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return \count($this->children);
    }

    /**
     * @return \Traversable|LeafInterface[]|RuleInterface[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * @param int $index
     * @return LeafInterface|RuleInterface|NodeInterface|mixed
     */
    public function getChild(int $index)
    {
        return $this->children[$index] ?? null;
    }

    /**
     * @return iterable|LeafInterface[]|RuleInterface[]|NodeInterface[]
     */
    public function getChildren(): iterable
    {
        return $this->children;
    }
}
