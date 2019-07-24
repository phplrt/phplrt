<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler;

use Phplrt\Assembler\Dependency\DependencyInterface;

/**
 * Interface AssemblerInterface
 */
interface AssemblerInterface
{
    /**
     * @param string $fqn
     * @return DependencyInterface
     */
    public function create(string $fqn): DependencyInterface;

    /**
     * @param string $fqn
     * @param \Closure|null $match
     * @return AssemblerInterface
     */
    public function with(string $fqn, \Closure $match = null): self;

    /**
     * @param string $class
     * @param \Closure|null $match
     * @return GeneratorInterface
     */
    public function build(string $class, \Closure $match = null): GeneratorInterface;
}
