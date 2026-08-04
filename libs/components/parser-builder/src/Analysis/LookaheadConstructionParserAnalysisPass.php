<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Analysis;

use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Predicate;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Describes which tokens each rule of the grammar may begin with.
 *
 * Which of the rules may be recognized without consuming a token is found out
 * along the way, and it is the same answer: a rule reading the empty input may
 * begin with anything at all.
 *
 * TODO Refactor lookahead table builder (expand method is sucks)
 */
final readonly class LookaheadConstructionParserAnalysisPass implements
    ParserAnalysisPassInterface
{
    public function process(ParserResultContext $context): void
    {
        $startTokens = [];
        $matchesEmptyInput = [];

        foreach ($context->grammar as $ruleId => $_) {
            $startTokens[$ruleId] = [];
            $matchesEmptyInput[$ruleId] = false;
        }

        // The rules refer to each other, so the sets grow until they stop
        // changing
        do {
            $changed = false;

            foreach ($context->grammar as $ruleId => $rule) {
                $before = [\count($startTokens[$ruleId]), $matchesEmptyInput[$ruleId]];

                $this->expand($rule, $startTokens, $matchesEmptyInput, $ruleId);

                if ($before !== [\count($startTokens[$ruleId]), $matchesEmptyInput[$ruleId]]) {
                    $changed = true;
                }
            }
        } while ($changed);

        $context->lookahead = self::merge($startTokens, $matchesEmptyInput);
    }

    /**
     * Returns the tokens each rule may begin with, or {@see null} for a rule
     * that may begin with any of them.
     *
     * A rule reading the empty input reads it wherever it stands, so there is
     * no token such a rule may be rejected by and its own set says nothing
     * about it: what has been found out about the rules is one answer from
     * here on.
     *
     * The sets are filled in while the rules refer to each other, so they are
     * ordered here as well: the order they end up in is the order the grammar
     * has been walked in rather than one that means anything.
     *
     * @param array<int, array<int, true>> $startTokens
     * @param array<int, bool> $matchesEmptyInput
     * @return array<int, array<int, true>|null>
     */
    private static function merge(array $startTokens, array $matchesEmptyInput): array
    {
        $result = [];

        foreach ($startTokens as $rule => $tokens) {
            if ($matchesEmptyInput[$rule] ?? true) {
                $result[$rule] = null;

                continue;
            }

            \ksort($tokens);

            $result[$rule] = $tokens;
        }

        \ksort($result);

        return $result;
    }

    /**
     * @param array<int, array<int, true>> $startTokens
     * @param array<int, bool> $matchesEmptyInput
     */
    private function expand(RuleInterface $definition, array &$startTokens, array &$matchesEmptyInput, int $rule): void
    {
        switch (true) {
            case $definition instanceof Lexeme:
                $startTokens[$rule][$definition->tokenId] = true;
                break;

            case $definition instanceof Concatenation:
                $optional = true;

                foreach ($definition->ruleIds as $inner) {
                    $startTokens[$rule] += $startTokens[$inner];

                    if (!$matchesEmptyInput[$inner]) {
                        $optional = false;

                        break;
                    }
                }

                $matchesEmptyInput[$rule] = $optional;
                break;

            case $definition instanceof Alternation:
                foreach ($definition->ruleIds as $inner) {
                    $startTokens[$rule] += $startTokens[$inner];

                    if ($matchesEmptyInput[$inner]) {
                        $matchesEmptyInput[$rule] = true;
                    }
                }
                break;

            case $definition instanceof Optional:
                $startTokens[$rule] += $startTokens[$definition->ruleId];
                $matchesEmptyInput[$rule] = true;
                break;

            case $definition instanceof Repetition:
                $startTokens[$rule] += $startTokens[$definition->ruleId];
                $matchesEmptyInput[$rule] = $definition->min === 0 || $matchesEmptyInput[$definition->ruleId];
                break;

            case $definition instanceof Predicate:
                /**
                 * A predicate reads nothing, so it begins with no token at all
                 * and the rule behind it decides what comes first.
                 */
                $matchesEmptyInput[$rule] = true;
                break;
        }
    }
}
