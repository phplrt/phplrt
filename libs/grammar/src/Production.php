<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Grammar;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Grammar\ProductionInterface;

abstract class Production extends Rule implements ProductionInterface
{
    /**
     * @param array $children
     * @param mixed $result
     * @return array
     */
    protected function mergeWith(array $children, mixed $result): array
    {
        if (\is_array($result)) {
            return \array_merge($children, $result);
        }

        /** @psalm-suppress MixedAssignment */
        $children[] = $result;

        return $children;
    }
}
