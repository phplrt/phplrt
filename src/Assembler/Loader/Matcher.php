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
 * Class Matcher
 */
class Matcher implements MatcherInterface
{
    /**
     * @var array|\Closure[]
     */
    private $rules = [];

    /**
     * @var array|\Closure[]
     */
    private $filters = [];

    /**
     * @param string $namespace
     * @return MatcherInterface|$this
     */
    public function namespaced(string $namespace): MatcherInterface
    {
        return $this->only(static function (string $fqn) use ($namespace): bool {
            return \strpos($fqn, \trim($namespace, '\\')) === 0;
        });
    }

    /**
     * @param \Closure $filter
     * @return MatcherInterface|$this
     */
    public function only(\Closure $filter): MatcherInterface
    {
        $this->rules[] = $filter;

        return $this;
    }

    /**
     * @param \Closure $filter
     * @return MatcherInterface|$this
     */
    public function except(\Closure $filter): MatcherInterface
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @param string $name
     * @param DependencyInterface $from
     * @return bool
     */
    public function match(string $name, DependencyInterface $from): bool
    {
        foreach ($this->filters as $filter) {
            if (! $filter($name, $from)) {
                return false;
            }
        }

        foreach ($this->rules as $filter) {
            if ($filter($name, $from)) {
                return true;
            }
        }

        return false;
    }
}
