<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Analysis;

use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Lexeme;

/**
 * Describes which alternatives of an alternation are worth trying on which
 * token.
 *
 * An alternative is rejected by the token the reading is at before it reads
 * anything of its own, so more than half of them are entered only to be given
 * up at once. Which of them stand a chance is a question about that token
 * rather than about the input, so it is answered here instead of being asked
 * again on every alternation the reading walks through.
 *
 * The answer is only as good as the tables it is read off, so a grammar that
 * has not been described is left alone: such an alternation is recognized by
 * trying every alternative it has.
 */
final readonly class ChoicePredictionConstructionParserAnalysisPass implements
    ParserAnalysisPassInterface
{
    public function process(ParserResultContext $context): void
    {
        // Nothing is known about the rules, so nothing may be ruled out
        if ($context->lookahead === []) {
            return;
        }

        $result = [];

        foreach ($context->grammar as $id => $rule) {
            if (!$rule instanceof Alternation) {
                continue;
            }

            $rows = $this->expand($context, $rule);

            if ($rows !== []) {
                $result[$id] = $rows;
            }
        }

        $context->choicePrediction = $result;
    }

    /**
     * Returns the alternatives of the given alternation worth trying, indexed
     * by the token the reading is at.
     *
     * @return array<int, list<int>>
     */
    private function expand(ParserResultContext $context, Alternation $rule): array
    {
        $firsts = [];
        $tokens = [];

        foreach ($rule->ruleIds as $alternative) {
            $firsts[$alternative] = $first = $this->calculateStartTokens($context, $alternative);

            if ($first !== null) {
                $tokens += $first;
            }
        }

        $result = [];

        foreach ($tokens as $token => $_) {
            $candidates = [];

            foreach ($rule->ruleIds as $alternative) {
                $first = $firsts[$alternative];

                if ($first === null || isset($first[$token])) {
                    $candidates[] = $alternative;
                }
            }

            // A token that rules nothing out is a token the analysis has
            // nothing to say about, and saying nothing costs nothing
            if ($candidates !== $rule->ruleIds) {
                $result[$token] = $candidates;
            }
        }

        return $result;
    }

    /**
     * Returns the tokens the given rule may begin with, or {@see null} in case
     * of it may begin with any of them.
     *
     * @return array<int, true>|null
     */
    private function calculateStartTokens(ParserResultContext $context, int $rule): ?array
    {
        $definition = $context->grammar[$rule] ?? null;

        // A terminal is rejected by the token itself rather than by the table,
        // so the only token it may begin with is read off the rule
        if ($definition instanceof Lexeme) {
            return [$definition->tokenId => true];
        }

        return $context->lookahead[$rule] ?? null;
    }
}
