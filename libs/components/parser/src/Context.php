<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * The state of the analysis at the moment a grammar rule is reduced.
 *
 * The very same context describes every rule of a single source, filled in
 * anew before each rule is reduced, so it is only valid while the rule it
 * describes is being built: a reducer that keeps it past its own call reads
 * whatever rule has been reduced since. Anything worth keeping is to be read
 * off the context and kept on its own.
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
         * The position the rule starts at, counted in bytes from the beginning
         * of the source.
         *
         * A rule containing no tokens starts at the position the reading of
         * the source has reached.
         *
         * @var int<0, max>
         */
        public int $begin = 0,
        /**
         * The number of bytes the rule has been recognized from.
         *
         * @var int<0, max>
         */
        public int $length = 0,
    ) {}
}
