<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Definition;

/**
 * A named piece of an expression, which every expression referring to it is
 * written with instead of spelling that piece again.
 *
 * A fragment recognizes nothing on its own: it is written into the expressions
 * referring to it while the lexer is being built and never becomes a token of
 * its own.
 */
final class FragmentDefinition extends Definition
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $pattern
     */
    public function __construct(
        /**
         * The name the expressions refer to this piece by.
         *
         * @var non-empty-string
         */
        public string $name,
        /**
         * The piece of an expression the name stands for.
         *
         * @var non-empty-string
         */
        public string $pattern,
    ) {}

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return \sprintf('/%s/ (%s)', \addcslashes($this->pattern, '/'), $this->name);
    }
}
