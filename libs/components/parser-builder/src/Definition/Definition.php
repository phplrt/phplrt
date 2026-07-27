<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

abstract class Definition implements \Stringable
{
    public private(set) ?SourceReference $context = null;

    /**
     * @param non-empty-string $pathname
     * @param int<0, max> $offset
     * @return $this
     */
    public function setSource(string $pathname, int $offset): self
    {
        $this->context = new SourceReference($pathname, $offset);

        return $this;
    }
}
