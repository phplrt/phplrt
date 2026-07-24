<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing\Result;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * A successful recognition result: the parse tree.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser\Internal\Tracing
 */
final readonly class Success extends Result
{
    public const int ENTER = 0;
    public const int LEAVE = 1;
    public const int TOKEN = 2;

    public function __construct(
        /**
         * @var list<int>
         */
        public array $type,
        /**
         * @var list<int|TokenInterface>
         */
        public array $node,
    ) {}
}
