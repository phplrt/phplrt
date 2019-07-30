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
 * Class Anonymous
 */
class Anonymous extends Node
{
    /**
     * @var array|NodeInterface[]
     */
    private $children;

    /**
     * Anonymous constructor.
     *
     * @param int $type
     * @param array $attributes
     * @param array $children
     */
    public function __construct(int $type, array $attributes = [], array $children = [])
    {
        parent::__construct($type, $attributes);

        $this->children = $children;
    }

    /**
     * @param string|int $name
     * @return NodeInterface|null
     */
    public function __get($name): ?NodeInterface
    {
        \assert(\is_scalar($name));

        return $this->getChild($name);
    }

    /**
     * @param string|int $name
     * @return NodeInterface|null
     */
    private function getChild($name): ?NodeInterface
    {
        return $this->children[$name] ?? null;
    }

    /**
     * @param string|int $name
     * @param NodeInterface $node
     * @return void
     */
    private function setChild($name, NodeInterface $node): void
    {
        $this->children[$name] = $node;
    }

    /**
     * @param string|int $name
     * @return void
     */
    private function removeChild($name): void
    {
        unset($this->children[$name]);
    }

    /**
     * @param string|int $name
     * @param NodeInterface|null $value
     * @return void
     */
    public function __set($name, $value)
    {
        \assert(\is_scalar($name));
        \assert($value === null || $value instanceof NodeInterface);

        if ($value === null) {
            $this->removeChild($name);
        } else {
            $this->setChild($name, $value);
        }
    }

    /**
     * @return \Traversable|NodeInterface[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }
}
