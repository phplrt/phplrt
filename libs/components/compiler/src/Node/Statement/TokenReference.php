<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes the declared token of the lexer.
 *
 * For example,
 * ```
 * <T_NAME>        // the token is kept in the syntax tree
 * ::T_COMMA::     // the token is only consumed
 * ```
 */
final readonly class TokenReference extends Statement
{
    /**
     * @param non-empty-string $name
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name of the token to recognize, as it is written.
         */
        public string $name,
        /**
         * Contains {@see true} in case of the token is written as "<T_NAME>",
         * so its value is kept in the syntax tree
         */
        public bool $isKept = true,
        int $offset = 0,
    ) {
        parent::__construct(offset: $offset);
    }
}
