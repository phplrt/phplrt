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
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Grammar\ProductionInterface;

/**
 * Class Production
 */
abstract class Production extends Rule implements ProductionInterface
{
    /**
     * @param array|NodeInterface[] $children
     * @param iterable|NodeInterface|TokenInterface $result
     * @return array|NodeInterface[]
     */
    protected function mergeWith(array $children, $result): array
    {
        if (\is_array($result)) {
            return \array_merge($children, $result);
        }

        $children[] = $result;

        return $children;
    }
}
