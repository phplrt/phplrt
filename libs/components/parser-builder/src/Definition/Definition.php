<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

abstract class Definition implements \Stringable
{
    public private(set) ?DefinitionContext $context = null;

    /**
     * @param non-empty-string $pathname
     * @param int<0, max> $offset
     * @return $this
     */
    public function setSource(string $pathname, int $offset): self
    {
        $this->context = new DefinitionContext($pathname, $offset);

        return $this;
    }
}
