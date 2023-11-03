<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

abstract class Terminal extends Rule implements TerminalInterface
{
    /**
     * @readonly Should not be modified in runtime.
     */
    public bool $keep = true;

    public function __construct(bool $keep)
    {
        $this->keep = $keep;
    }

    public function getTerminals(array $rules): iterable
    {
        return [$this];
    }

    final public function isKeep(): bool
    {
        return $this->keep;
    }
}
