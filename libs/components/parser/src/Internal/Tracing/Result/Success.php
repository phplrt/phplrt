<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing\Result;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * A successful recognition result: the parse tree.
 *
 * Contains 2 linear packed arrays
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser\Internal\Tracing
 */
final class Success extends Result
{
    public const int TYPE_ENTER = 0;
    public const int TYPE_LEAVE = 1;
    public const int TYPE_TOKEN = 2;

    public function __construct(
        /**
         * @var array<int<0, max>, int>
         */
        public array $types,
        /**
         * @var array<int<0, max>, int|TokenInterface>
         */
        public array $references,
        /**
         * The number of meaningful entries; anything beyond it must be ignored.
         *
         * @var int<0, max>
         */
        public int $length,
    ) {}
}
