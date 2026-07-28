<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes a token declared right where it is used.
 *
 * Such a token has no name of its own and is never kept in the syntax tree, so
 * it stands for the punctuation a rule reads but says nothing about.
 *
 * For example,
 * ```
 * "\+"
 * ```
 */
final readonly class InlinePattern extends Statement
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The regular expression recognizing the token, with the escaping of
         * the surrounding quotes already undone.
         */
        public string $pattern,
        int $offset = 0,
    ) {
        parent::__construct(offset: $offset);
    }
}
