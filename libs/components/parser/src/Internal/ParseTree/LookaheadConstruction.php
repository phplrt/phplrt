<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\ParseTree;

use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Describes which tokens each rule of the grammar may begin with.
 *
 * TODO Move to compiler
 * TODO Refactor lookahead table builder (expand method is sucks)
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final readonly class LookaheadConstruction
{
    /**
     * @param list<RuleInterface> $grammar
     */
    public static function compute(array $grammar): Lookahead
    {
        $first = [];
        $nullable = [];

        foreach ($grammar as $ruleId => $_) {
            $first[$ruleId] = [];
            $nullable[$ruleId] = false;
        }

        // The rules refer to each other, so the sets grow until they stop
        // changing
        do {
            $changed = false;

            foreach ($grammar as $ruleId => $rule) {
                $before = [\count($first[$ruleId]), $nullable[$ruleId]];

                self::expand($rule, $first, $nullable, $ruleId);

                if ($before !== [\count($first[$ruleId]), $nullable[$ruleId]]) {
                    $changed = true;
                }
            }
        } while ($changed);

        return new Lookahead(
            first: $first,
            nullable: $nullable,
        );
    }

    /**
     * @param array<int, array<int, true>> $first
     * @param array<int, bool> $nullable
     */
    private static function expand(RuleInterface $definition, array &$first, array &$nullable, int $rule): void
    {
        switch (true) {
            case $definition instanceof Lexeme:
                $first[$rule][$definition->tokenId] = true;
                break;

            case $definition instanceof Concatenation:
                $optional = true;

                foreach ($definition->rules as $inner) {
                    $first[$rule] += $first[$inner];

                    if (!$nullable[$inner]) {
                        $optional = false;

                        break;
                    }
                }

                $nullable[$rule] = $optional;
                break;

            case $definition instanceof Alternation:
                foreach ($definition->ruleIds as $inner) {
                    $first[$rule] += $first[$inner];

                    if ($nullable[$inner]) {
                        $nullable[$rule] = true;
                    }
                }
                break;

            case $definition instanceof Optional:
                $first[$rule] += $first[$definition->ruleId];
                $nullable[$rule] = true;
                break;

            case $definition instanceof Repetition:
                $first[$rule] += $first[$definition->ruleId];
                $nullable[$rule] = $definition->min === 0 || $nullable[$definition->ruleId];
                break;
        }
    }
}
