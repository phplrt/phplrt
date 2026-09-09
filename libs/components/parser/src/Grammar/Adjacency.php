<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * Requires the surrounding tokens to be written one right after another.
 *
 * A lexer reports what it has read and says nothing about what it has stepped
 * over in between, so a stream of tokens tells "a\b" and "a \ b" apart by
 * nothing: both of them are the very same three tokens. This is the rule that
 * tells them apart. It reads nothing of its own and only answers whether the
 * token behind it ends exactly where the token ahead of it begins.
 *
 * For example, a name written of the parts nothing stands between, where #1
 * recognizes a name and #2 a separator:
 * ```php
 * new Concatenation([
 *     1, // Lexeme(T_NAME)
 *     3, // Adjacency()
 *     2, // Lexeme(T_SEPARATOR)
 * ]);
 * ```
 *
 * The very same rule written in pp3, where "~" means the tokens around it are
 * written with nothing in between:
 * ```pp3
 * Rule : <T_NAME> ~ ::T_SEPARATOR:: ;
 * ```
 *
 * The answer is read off the source rather than off the stream, so it does not
 * depend on what has been written in between: a space a lexer never reports and
 * a comment it reports on a channel of its own are both something written
 * there.
 *
 * Note: A rule of this kind reads no input, so it recognizes the empty input
 *       the way a predicate does. The only question is whether it recognizes
 *       it here:
 *
 * ```math
 * L(\sim) = \{\, \varepsilon \,\}
 * ```
 *
 * @readonly
 */
final class Adjacency implements TerminalInterface
{
    public function __construct(
        /**
         * Contains {@see true} in case of the tokens must be written with
         * nothing in between, or {@see false} in case of they must not.
         */
        public readonly bool $isExpected = true,
    ) {}
}
