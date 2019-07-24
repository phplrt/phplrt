<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Dependency;

use PhpParser\Node;

/**
 * Interface UserDependencyInterface
 */
interface UserDependencyInterface extends DependencyInterface
{
    /**
     * @param \Closure $closure
     * @return iterable|Node[]
     */
    public function lookup(\Closure $closure): iterable;

    /**
     * @return iterable|Node[]
     */
    public function getAst(): iterable;

    /**
     * @return string
     */
    public function getFileName(): string;
}
