<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * Recognizes the rule it refers to, if the input matches it.
 *
 * The rule is recognized either way. Whether the inner one has matched only
 * decides how much of the input has been read. An inner rule that has not
 * matched leaves nothing behind, and the reading goes on from where the
 * optional rule has been entered.
 *
 * For example, a rule reading a sign that may be omitted, where #1 recognizes
 * a sign:
 * ```php
 * new Optional(ruleId: 1);
 * ```
 *
 * The very same rule written in EBNF:
 * ```ebnf
 * rule = [ sign ] ;
 * ```
 *
 * and in pp2:
 * ```pp2
 * Rule : Sign()? ;
 * ```
 *
 * The rule reads everything the inner one reads, and nothing at all as well:
 *
 * ```math
 * L(A?) = L(A) \cup \{\, \varepsilon \,\}
 * ```
 */
final readonly class Optional implements ProductionInterface
{
    public function __construct(
        public int $ruleId,
    ) {}
}
