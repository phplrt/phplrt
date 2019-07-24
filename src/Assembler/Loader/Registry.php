<?php
/**
 * This file is part of assembler package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Loader;

use Phplrt\Assembler\Dependency\DependencyInterface;

/**
 * Class Registry
 */
class Registry implements \IteratorAggregate
{
    /**
     * @var array|DependencyInterface[]
     */
    public $dependencies = [];

    /**
     * @param DependencyInterface $dependency
     * @return Registry|void
     */
    public function add(DependencyInterface $dependency): self
    {
        $this->dependencies[$dependency->getName()] = $dependency;

        return $this;
    }

    /**
     * @param string $fqn
     * @return bool
     */
    public function has(string $fqn): bool
    {
        return isset($this->dependencies[$fqn]);
    }

    /**
     * @param string $fqn
     * @return DependencyInterface
     */
    public function get(string $fqn): DependencyInterface
    {
        return $this->dependencies[$fqn];
    }

    /**
     * @param DependencyInterface $dependency
     * @return bool
     */
    public function loaded(DependencyInterface $dependency): bool
    {
        return $this->has($dependency->getName());
    }

    /**
     * @return \Traversable|DependencyInterface[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->dependencies);
    }
}
