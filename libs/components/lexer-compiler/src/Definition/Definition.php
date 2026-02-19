<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Definition;

use Phplrt\Contracts\Source\ReadableInterface;

abstract class Definition implements \Stringable
{
    public private(set) ?DefinitionContext $context = null;

    /**
     * @param int<0, max> $offset
     * @return $this
     */
    public function setSource(ReadableInterface $source, int $offset): self
    {
        $this->context = new DefinitionContext($source, $offset);

        return $this;
    }
}
