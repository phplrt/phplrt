<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes the token declared by the statement reading it, spelled as the
 * text it matches.
 *
 * Such a token has no name of its own and is never kept in the syntax tree, so
 * it stands for the punctuation a rule reads but says nothing about.
 */
final readonly class InlineValue extends Statement
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The text the token is recognized by, with everything belonging to
         * the grammar file already gone.
         *
         * The value is matched as it is written rather than as an expression,
         * so whatever is special to a regular expression is not special here.
         */
        public string $value,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
