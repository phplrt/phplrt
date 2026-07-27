<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * The state of the analysis at the moment a grammar rule is reduced.
 *
 * @readonly
 */
final class Context
{
    public function __construct(
        /**
         * The identifier of the rule being reduced.
         */
        public int $rule,
        /**
         * The source the rule has been recognized in, which is what an error
         * refers to.
         */
        public readonly ReadableInterface $source,
        /**
         * The source code of the {@see $source}, already read.
         */
        public readonly string $content,
        /**
         * The last token recognized before the rule was completed or "null"
         * in case the rule contains no tokens at all.
         */
        public ?TokenInterface $token,
    ) {}
}
