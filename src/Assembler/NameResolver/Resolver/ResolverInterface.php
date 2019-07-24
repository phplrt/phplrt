<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\NameResolver\Resolver;

use PhpParser\Node\Name;

/**
 * Interface ResolverInterface
 */
interface ResolverInterface
{
    /**
     * @param Name $name
     * @param \Closure $export
     * @return mixed|null
     */
    public function resolve(Name $name, \Closure $export);
}
