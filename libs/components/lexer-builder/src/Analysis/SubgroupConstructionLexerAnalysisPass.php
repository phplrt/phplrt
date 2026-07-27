<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Analysis;

use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;

/**
 * Describes how many subgroups each token definition has.
 *
 * The subgroups of all the token definitions share their numbers, so what a
 * token has captured cannot be told from the pattern alone: a definition with
 * two subgroups is reported the very same way as the one with five. Counting
 * them beforehand is what keeps a token that captures nothing from being read
 * as if it did.
 */
final readonly class SubgroupConstructionLexerAnalysisPass implements
    LexerAnalysisPassInterface
{
    public function process(LexerResultContext $context): void
    {
        $result = [];

        foreach ($context->tokens as $id => $definition) {
            if (!$definition instanceof RegexTokenDefinition) {
                continue;
            }

            $count = self::countSubgroups($definition->regex);

            if ($count > 0) {
                $result[$id] = $count;
            }
        }

        $context->subgroups = $result;
    }

    /**
     * Asks PCRE how many subgroups the given expression has.
     *
     * The empty alternative makes the expression match nothing at all, while
     * every subgroup is still reported, and a named subgroup is reported twice:
     * under its name and under its number.
     *
     * @param non-empty-string $regex
     * @return int<0, max>
     */
    private static function countSubgroups(string $regex): int
    {
        $pattern = \sprintf('/(?:%s)|/u', \addcslashes($regex, '/#'));

        if (@\preg_match($pattern, '', $matches, \PREG_UNMATCHED_AS_NULL) !== 1) {
            return 0;
        }

        $result = 0;

        foreach ($matches as $index => $_) {
            if (\is_int($index)) {
                ++$result;
            }
        }

        // The expression itself is reported along with its subgroups
        return \max(0, $result - 1);
    }
}
