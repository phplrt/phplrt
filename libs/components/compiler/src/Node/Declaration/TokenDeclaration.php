<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

/**
 * Declares a token of the lexer.
 *
 * For example,
 * ```
 * %token T_NAME             [a-zA-Z_]\w*+
 * %token string:T_QUOTE     "               -> default
 * %skip  T_WHITESPACE       \s++
 * ```
 */
final readonly class TokenDeclaration extends Declaration
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $pattern
     * @param non-empty-string|null $state
     * @param non-empty-string|null $next
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name of the token being declared.
         */
        public string $name,
        /**
         * The regular expression recognizing the token, as it is written.
         */
        public string $pattern,
        /**
         * The name of the lexer state the token belongs to, or {@see null} in
         * case of the token belongs to the initial state.
         */
        public ?string $state = null,
        /**
         * The name of the lexer state the token switches to, or {@see null} in
         * case of the token does not affect the lexer state.
         */
        public ?string $next = null,
        /**
         * Contains {@see true} in case of the token is declared using "%skip",
         * so it is read but never reaches the parser
         */
        public bool $isHidden = false,
        int $offset = 0,
    ) {
        parent::__construct(offset: $offset);
    }
}
