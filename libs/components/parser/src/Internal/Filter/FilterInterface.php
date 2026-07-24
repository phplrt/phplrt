<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Filter;

use Phplrt\Contracts\Lexer\TokenInterface;

interface FilterInterface
{
    /**
     * @template TArgValue of TokenInterface
     * @param iterable<mixed, TArgValue> $tokens
     * @return iterable<array-key, TArgValue>
     */
    public function apply(iterable $tokens): iterable;
}
