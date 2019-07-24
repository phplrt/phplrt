<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Loader;

use Phplrt\Assembler\Dependency\DependencyInterface;

/**
 * Interface MatcherInterface
 */
interface MatcherInterface
{
    /**
     * @param \Closure $filter
     * @return MatcherInterface|$this
     */
    public function only(\Closure $filter): self;

    /**
     * @param \Closure $filter
     * @return MatcherInterface|$this
     */
    public function except(\Closure $filter): self;

    /**
     * @param string $namespace
     * @return MatcherInterface|$this
     */
    public function namespaced(string $namespace): self;

    /**
     * @param string $name
     * @param DependencyInterface $from
     * @return bool
     */
    public function match(string $name, DependencyInterface $from): bool;
}
