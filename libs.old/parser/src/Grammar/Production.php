<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

abstract class Production extends Rule implements ProductionInterface
{
    /**
     * @param list<NodeInterface|TokenInterface> $children
     * @param NodeInterface|TokenInterface|array $result
     *
     * @return list<NodeInterface|TokenInterface>
     */
    protected function mergeWith(array $children, mixed $result): array
    {
        if (\is_array($result)) {
            return \array_merge($children, $result);
        }

        $children[] = $result;

        return $children;
    }
}
