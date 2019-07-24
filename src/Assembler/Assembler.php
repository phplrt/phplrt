<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler;

use Phplrt\Assembler\Dependency\ClassDependency;
use Phplrt\Assembler\Dependency\DependencyInterface;
use Phplrt\Assembler\Dependency\FunctionDependency;
use Phplrt\Assembler\Dependency\InterfaceDependency;
use Phplrt\Assembler\Dependency\TraitDependency;
use Phplrt\Assembler\Dependency\UserDependencyInterface;
use Phplrt\Assembler\Exception\DependencyException;
use Phplrt\Assembler\Loader\Loader;
use Phplrt\Assembler\Loader\Matcher;
use Phplrt\Assembler\Loader\Registry;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;

/**
 * Class Assembler
 */
class Assembler implements AssemblerInterface
{
    /**
     * @var Registry
     */
    private $dependencies;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * Assembler constructor.
     */
    public function __construct()
    {
        $this->parser       = new Parser();
        $this->dependencies = new Registry();
    }

    /**
     * @param string $fqn
     * @param \Closure|null $match
     * @return AssemblerInterface|$this
     * @throws \ReflectionException
     */
    public function with(string $fqn, \Closure $match = null): AssemblerInterface
    {
        $this->load($this->dependencies, $fqn, $match);

        return $this;
    }

    /**
     * @param Registry $registry
     * @param string $fqn
     * @param \Closure|null $match
     * @return Registry
     * @throws \ReflectionException
     */
    private function load(Registry $registry, string $fqn, ?\Closure $match): Registry
    {
        $registry->add($dependency = $this->create($fqn));

        $loader = new Loader($this, $registry, $matcher = new Matcher());

        if ($match) {
            $match($matcher);
        }

        foreach ($loader->resolve($dependency) as $relation) {
            $registry->add($relation);
        }

        return $registry;
    }

    /**
     * @param string $fqn
     * @return DependencyInterface
     * @throws \ReflectionException
     */
    public function create(string $fqn): DependencyInterface
    {
        switch (true) {
            case \interface_exists($fqn):
                return new InterfaceDependency($fqn, $this->parser);

            case \trait_exists($fqn):
                return new TraitDependency($fqn, $this->parser);

            case \class_exists($fqn):
                return new ClassDependency($fqn, $this->parser);

            case \function_exists($fqn):
                return new FunctionDependency($fqn, $this->parser);

            default:
                throw new DependencyException('Unrecognized dependency type ' . $fqn);
        }
    }

    /**
     * @param string $fqn
     * @param \Closure|null $match
     * @return GeneratorInterface
     * @throws \ReflectionException
     */
    public function build(string $fqn, \Closure $match = null): GeneratorInterface
    {
        $registry = $this->load(clone $this->dependencies, $fqn, $match);
        $registry->add($this->create($fqn));

        return new Generator($this->parser, $fqn, $this->classes($fqn, $registry));
    }

    /**
     * @param string $root
     * @param Registry $registry
     * @return \Traversable|Node[]
     */
    private function classes(string $root, Registry $registry): \Traversable
    {
        foreach ($registry as $dependency) {
            if ($dependency instanceof UserDependencyInterface) {
                yield $dependency->lookup(static function (Name $name) use ($root, $registry): Name {
                    if ($root === $name->toString() || ! $registry->has($name->toString())) {
                        return new FullyQualified($root);
                    }

                    return new Name($registry->get($name->toString())->getAlias());
                });
            }
        }
    }
}
