<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

/**
 * Declares a named piece of an expression.
 *
 * A piece recognizes nothing on its own: every expression referring to it is
 * written with that piece instead of spelling it again.
 */
final readonly class FragmentDeclaration extends Declaration
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The name the expressions refer to the piece by.
         *
         * @var non-empty-string
         */
        public string $name,
        /**
         * The piece of an expression the name stands for, as it is written.
         *
         * @var non-empty-string
         */
        public string $pattern,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
