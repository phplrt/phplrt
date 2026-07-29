<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes the declared token of the lexer.
 */
final readonly class TokenReference extends Statement
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The name of the token to recognize, as it is written.
         *
         * @var non-empty-string
         */
        public string $name,
        /**
         * Contains {@see true} in case of the value of the token is kept in
         * the syntax tree, or {@see false} in case of the token is only
         * consumed, such as a comma or a bracket
         */
        public bool $isKept = true,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
