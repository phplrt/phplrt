<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Internal;

use Phplrt\Lexer\Internal\Tokenizer\TokenizerInterface;

/**
 * A single lexer state: something able to read the source and the table
 * describing where to go afterwards.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Lexer
 */
final readonly class State
{
    public function __construct(
        public TokenizerInterface $tokenizer,
        /**
         * A map of token ID and the state it switches to.
         *
         * A token that is missing from this table (which is always the case
         * for an embedded lexer) returns the control to the state the lexer
         * came from, same as an explicit {@see null} value.
         *
         * @var array<int, non-empty-string|null>
         */
        public array $transitions = [],
    ) {}
}
