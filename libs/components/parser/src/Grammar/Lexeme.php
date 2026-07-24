<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

final readonly class Lexeme implements TerminalInterface
{
    public function __construct(
        public int $tokenId,
        /**
         * Whether the token is kept in the result (a name, a literal) or only
         * consumed (punctuation such as a comma or a bracket).
         */
        public bool $keep = true,
    ) {}
}
