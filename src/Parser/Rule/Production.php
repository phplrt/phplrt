<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

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
    protected function merge(array $children, $result): array
    {
        if (\is_array($result)) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            return \array_merge($children, $result);
        }

        $children[] = $result;

        return $children;
    }

    /**
     * @param mixed $result
     * @return array
     */
    protected function toArray($result): array
    {
        return \is_array($result) ? $result : [$result];
    }
}
