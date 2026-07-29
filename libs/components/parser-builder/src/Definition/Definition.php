<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

use Phplrt\Contracts\Source\ReadableInterface;

abstract class Definition implements \Stringable
{
    /**
     * The place of the source code this definition has been written in, in
     * case it has been written at all rather than built by hand.
     */
    public private(set) ?SourceReference $context = null;

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @return $this
     */
    public function setSource(ReadableInterface $source, int $offset, int $length = 0): self
    {
        $this->context = new SourceReference($source, $offset, $length);

        return $this;
    }
}
