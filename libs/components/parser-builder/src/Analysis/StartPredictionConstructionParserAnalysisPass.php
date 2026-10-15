<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Analysis;

use Phplrt\Parser\Grammar\Adjacency;
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
 * A predicate standing in a sequence rules out some of the tokens the
 * elements after it may begin with: a positive one leaves only the tokens the
 * rule it looks for may begin with, and a negative one drops the tokens that
 * rule is recognized by on their own.
 *
 * TODO Refactor start prediction builder (expand method is sucks)
 *
 * @readonly
 */
final class StartPredictionConstructionParserAnalysisPass implements
    ParserAnalysisPassInterface
{
    public function process(ParserResultContext $context): void
    {
        $grammar = $context->grammar;
        $startTokens = [];
        $matchesEmptyInput = [];
        $rejectedTokens = [];

        foreach ($grammar as $ruleId => $rule) {
            $startTokens[$ruleId] = [];
            $matchesEmptyInput[$ruleId] = false;

            if ($rule instanceof Predicate && !$rule->isExpected) {
                $rejectedTokens[$ruleId] = self::calculateRecognizingTokens($grammar, $rule->ruleId);
            }
        }

        // The rules refer to each other, so the sets grow until they stop
        // changing
        do {
            $changed = false;

            foreach ($grammar as $ruleId => $rule) {
                $before = [\count($startTokens[$ruleId]), $matchesEmptyInput[$ruleId]];

                $this->expand($grammar, $rule, $startTokens, $matchesEmptyInput, $rejectedTokens, $ruleId);

                if ($before !== [\count($startTokens[$ruleId]), $matchesEmptyInput[$ruleId]]) {
                    $changed = true;
                }
            }
        } while ($changed);

        $prediction = self::merge($startTokens, $matchesEmptyInput);

        /**
         * A predicate reads nothing, so to the rules around it it may begin
         * with any token at all. A predicate expecting a rule fails on every
         * token that rule may not begin with though, so on its own it is
         * rejected by the very tokens the rule is.
         */
        foreach ($grammar as $ruleId => $rule) {
            if ($rule instanceof Predicate && $rule->isExpected) {
                $prediction[$ruleId] = $prediction[$rule->ruleId];
            }
        }

        $context->startPrediction = $prediction;

        $context->logger->info('Start prediction is known for {rules} rule(s), {empty} of them read an empty input', [
            'rules' => \count($context->startPrediction),
            'empty' => \count(\array_filter($context->startPrediction, static fn(?array $t): bool => $t === null)),
        ]);
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
     * Returns the tokens the given rule is recognized by on their own, so that
     * a rule standing at such a token is there for sure.
     *
     * @param array<int, RuleInterface> $grammar
     * @param array<int, true> $visited
     * @return array<int, true>
     */
    private static function calculateRecognizingTokens(array $grammar, int $rule, array $visited = []): array
    {
        $definition = $grammar[$rule] ?? null;

        if ($definition instanceof Lexeme) {
            return [$definition->tokenId => true];
        }

        if (!$definition instanceof Alternation || isset($visited[$rule])) {
            return [];
        }

        $visited[$rule] = true;
        $result = [];

        // Note: An alternative tried before the one recognized by the token
        //       may only read more of the input, so the alternation is there
        //       either way.
        foreach ($definition->ruleIds as $inner) {
            $result += self::calculateRecognizingTokens($grammar, $inner, $visited);
        }

        return $result;
    }

    /**
     * @param array<int, RuleInterface> $grammar
     * @param array<int, array<int, true>> $startTokens
     * @param array<int, bool> $matchesEmptyInput
     * @param array<int, array<int, true>> $rejectedTokens
     */
    private function expand(
        array $grammar,
        RuleInterface $definition,
        array &$startTokens,
        array &$matchesEmptyInput,
        array $rejectedTokens,
        int $rule,
    ): void {
        switch (true) {
            case $definition instanceof Lexeme:
                $startTokens[$rule][$definition->tokenId] = true;
                break;

            case $definition instanceof Concatenation:
                $optional = true;
                $predicates = [];

                foreach ($definition->ruleIds as $inner) {
                    $element = $grammar[$inner] ?? null;

                    if ($element instanceof Predicate) {
                        $predicates[$inner] = $element->ruleId;

                        continue;
                    }

                    $tokens = $startTokens[$inner];

                    // Every element read after a predicate begins at the very
                    // token the predicate has looked at
                    foreach ($predicates as $predicate => $body) {
                        if (isset($rejectedTokens[$predicate])) {
                            $tokens = \array_diff_key($tokens, $rejectedTokens[$predicate]);
                        } elseif (!$matchesEmptyInput[$body]) {
                            $tokens = \array_intersect_key($tokens, $startTokens[$body]);
                        }
                    }

                    $startTokens[$rule] += $tokens;

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
            case $definition instanceof Adjacency:
                /**
                 * A predicate reads nothing, so it begins with no token at all
                 * and the rule behind it decides what comes first.
                 */
                $matchesEmptyInput[$rule] = true;
                break;
        }
    }
}
