<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing\Result;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * A failed recognition result: where the input stopped matching the grammar.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser\Internal\Tracing
 */
final readonly class Failure extends Result
{
    public function __construct(
        /**
         * The token the analysis stopped on, if any.
         */
        public ?TokenInterface $token,
        /**
         * @var list<int>
         */
        public array $expected = [],
    ) {}
}
