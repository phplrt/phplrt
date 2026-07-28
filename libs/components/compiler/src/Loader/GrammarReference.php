<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Loader;

/**
 * Points at another grammar file the declarations are read from.
 *
 * This is what a loader says about a reference instead of handing over the
 * node it has read: the pathname is spelled the way the grammar spells it,
 * with everything belonging to the format (the quotes, the directive itself)
 * already gone.
 */
final readonly class GrammarReference
{
    public function __construct(
        /**
         * The pathname of the grammar file to read.
         *
         * The pathname is relative to the grammar the reference is written in
         * and the extension may be omitted, so it cannot be resolved without
         * knowing which grammar has been read.
         *
         * @var non-empty-string
         */
        public string $target,
        /**
         * The position of the grammar file the reference is written at, which
         * is what an error refers to.
         *
         * @var int<0, max>
         */
        public int $offset = 0,
        /**
         * The number of bytes the reference is written of.
         *
         * @var int<0, max>
         */
        public int $length = 0,
    ) {}
}
