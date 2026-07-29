<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * Looks at what comes next without reading it.
 *
 * The rule is recognized against the input the very same way as any other, but
 * whatever it has recognized is thrown away afterwards: neither the input nor
 * the result moves forward, so the only thing left is the answer. This is what
 * lets a rule say "not here". An alternative may refuse a position that
 * belongs to somebody else without spending a single token on finding out.
 *
 * For example, a rule refusing the position an opening parenthesis follows,
 * where #1 recognizes a parenthesis:
 * ```php
 * // Reads a name only while it is not a call
 * new Concatenation([
 *     2, // Predicate(ruleId: 1, isExpected: false)
 *     3, // Lexeme(T_NAME)
 * ]);
 * ```
 *
 * The very same rule written in PEG, where "&" means the rule must be
 * recognized at this position and "!" means it must not:
 * ```peg
 * rule <- !parenthesis name
 * ```
 *
 * Note: EBNF has nothing of the kind, and not by omission: it describes what
 *       a language contains, while a predicate describes how it is read.
 *       There is nothing to write down in a notation that never mentions the
 *       reading. The pp2 grammar has no syntax for predicates either, so such
 *       a rule can only be built by hand or by the parser builder.
 *
 * Both forms read nothing at all, so both of them recognize the empty input.
 * The only question is whether they recognize it here:
 *
 * ```math
 * L(\&A) = L(!A) = \{\, \varepsilon \,\}
 * ```
 */
final readonly class Predicate implements ProductionInterface
{
    public function __construct(
        public int $ruleId,
        /**
         * Contains {@see true} in case of the rule must be recognized at this
         * position, or {@see false} in case of it must not.
         */
        public bool $isExpected = true,
    ) {}
}
