<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * Recognizes a single token of the lexer.
 *
 * This is where the grammar ends: every other rule is written in terms of the
 * rules around it, while this one is written in terms of what the lexer has
 * already read. The token is addressed by its identifier rather than by its
 * value, so what it looks like in the source is none of the parser's business.
 *
 * For example, a rule reading a comma that the result says nothing about:
 * ```php
 * new Lexeme(tokenId: 42, keep: false);
 * ```
 *
 * The very same rule written in EBNF:
 * ```ebnf
 * rule = "," ;
 * ```
 *
 * and in pp2, where a token in double colons is only read, while a token in
 * angle brackets is kept in the result as well:
 * ```pp2
 * Rule : ::T_COMMA:: ;
 * ```
 *
 * Note: A terminal of EBNF is a character, because the notation describes the
 *       reading as a single pass over the source. Here it is split in two, so
 *       a terminal is a token: the lexer has turned the characters into them
 *       beforehand.
 */
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
