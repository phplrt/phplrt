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
 * Describes which tokens each rule of the grammar may begin with and which of
 * them may be recognized without consuming a token.
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

        $context->startTokens = $startTokens;
        $context->matchesEmptyInput = $matchesEmptyInput;
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
