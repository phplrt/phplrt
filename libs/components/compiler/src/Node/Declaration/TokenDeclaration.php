<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

/**
 * Declares a token of the lexer.
 */
final readonly class TokenDeclaration extends Declaration
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The name of the token being declared.
         *
         * @var non-empty-string
         */
        public string $name,
        /**
         * The regular expression recognizing the token, as it is written.
         *
         * @var non-empty-string
         */
        public string $pattern,
        /**
         * The name of the lexer state the token belongs to, or {@see null} in
         * case of the token belongs to the initial state.
         *
         * @var non-empty-string|null
         */
        public ?string $state = null,
        /**
         * The name of the lexer state the token switches to, or {@see null} in
         * case of the token does not affect the lexer state.
         *
         * @var non-empty-string|null
         */
        public ?string $next = null,
        /**
         * Contains {@see true} in case of the token is read but never reaches
         * the parser, such as the whitespace and the comments
         */
        public bool $isHidden = false,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
