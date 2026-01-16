<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

abstract class Terminal extends Rule implements TerminalInterface
{
    public function __construct(
        public readonly bool $keep = true,
    ) {}

    public function getTerminals(array $rules): iterable
    {
        return [$this];
    }

    final public function isKeep(): bool
    {
        return $this->keep;
    }
}
