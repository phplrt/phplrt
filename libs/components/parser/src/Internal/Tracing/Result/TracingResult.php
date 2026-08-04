<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing\Result;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * What the recognition has made of an input.
 *
 * Whatever the grammar has managed to read is here, and whether that was the
 * whole input is told by the class of the result and by nothing else.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
abstract class TracingResult
{
    public function __construct(
        /**
         * The recognized rules in the order they have been applied.
         *
         * A token stands for itself, a positive number is the identifier of the
         * rule the analysis enters and a negative one is the identifier of the
         * rule it leaves, decreased by one.
         *
         * @var array<int<0, max>, int|TokenInterface>
         */
        public array $entries = [],
        /**
         * The number of meaningful entries. Anything beyond it must be ignored.
         *
         * @var int<0, max>
         */
        public int $length = 0,
    ) {}
}
