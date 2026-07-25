<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * The state of the analysis at the moment a grammar rule is reduced.
 */
final readonly class Context
{
    public function __construct(
        /**
         * The identifier of the rule being reduced.
         *
         * @var int<0, max>
         */
        public int $rule,
        /**
         * The source code the rule has been recognized in.
         */
        public string $source,
        /**
         * The last token recognized before the rule was completed or "null"
         * in case the rule contains no tokens at all.
         */
        public ?TokenInterface $token,
    ) {}
}
