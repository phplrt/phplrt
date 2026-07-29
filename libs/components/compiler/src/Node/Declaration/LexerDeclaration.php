<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

use Phplrt\Compiler\Node\Reducer\CodeReducer;

/**
 * Declares a lexer reading a fragment of the source on its own.
 *
 * Such a lexer is written as the code building it rather than as the tokens it
 * recognizes, which is how a fragment that no regular expression can describe
 * is read: the grammar names the fragment and hands the reading over to
 * something written by hand.
 */
final readonly class LexerDeclaration extends Declaration
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The name of the state the lexer reads, as it is referred to by the
         * tokens entering it.
         *
         * @var non-empty-string
         */
        public string $name,
        /**
         * The code building the lexer.
         */
        public CodeReducer $lexer,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
