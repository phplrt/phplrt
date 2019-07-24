<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Loader;

use Phplrt\Assembler\AssemblerInterface;
use Phplrt\Assembler\Dependency\DependencyInterface;

/**
 * Class Loader
 */
class Loader
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var AssemblerInterface
     */
    private $assembler;

    /**
     * @var MatcherInterface
     */
    private $matcher;

    /**
     * Loader constructor.
     *
     * @param AssemblerInterface $assembler
     * @param Registry $registry
     * @param MatcherInterface $matcher
     */
    public function __construct(AssemblerInterface $assembler, Registry $registry, MatcherInterface $matcher)
    {
        $this->registry = $registry;
        $this->assembler = $assembler;
        $this->matcher = $matcher;
    }

    /**
     * @param DependencyInterface $dependency
     * @return \Traversable|DependencyInterface[]
     */
    public function resolve(DependencyInterface $dependency): \Traversable
    {
        $registry = clone $this->registry;

        $this->recursive($registry, $dependency, function (string $fqn, DependencyInterface $dependency) {
            return $this->matcher->match($fqn, $dependency);
        });

        return $registry;
    }

    /**
     * @param Registry $registry
     * @param DependencyInterface $dependency
     * @param \Closure $match
     * @return void
     */
    private function recursive(Registry $registry, DependencyInterface $dependency, \Closure $match): void
    {
        if (! $registry->loaded($dependency)) {
            $registry->add($dependency);
        }

        foreach ($dependency->getDependencies() as $name) {
            if ($registry->has($name->toString())) {
                continue;
            }

            $relation = $this->assembler->create($name->toString());

            if (! $match($name->toString(), $relation)) {
                continue;
            }

            $this->recursive($registry, $relation, $match);
        }
    }
}
