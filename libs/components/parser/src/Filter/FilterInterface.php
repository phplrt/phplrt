<?php

declare(strict_types=1);

namespace Phplrt\Parser\Filter;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * @template-extends \Traversable<mixed, TokenInterface>
 */
interface FilterInterface
{
    /**
     * @template TArgValue of TokenInterface
     * @param iterable<mixed, TArgValue> $tokens
     * @return iterable<array-key, TArgValue>
     */
    public function apply(iterable $tokens): iterable;
}
