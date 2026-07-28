<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\AlternationRuleDefinition;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\OptionalRuleDefinition;
use Phplrt\Parser\Builder\Definition\PredicateRuleDefinition;
use Phplrt\Parser\Builder\Definition\RepetitionRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenIdRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenNameRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenRuleDefinition;

/**
 * Merges the rules recognizing exactly the same input into a single one.
 *
 * The very same token or group of rules is usually mentioned by the grammar
 * more than once, and each mention costs a rule of the compiled parser along
 * with an entry in each of its tables.
 */
final readonly class DuplicateRuleParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        /**
         * Merging the rules makes the ones referring to them equal as well, so
         * the grammar is walked again until nothing changes.
         */
        do {
            /** @var array<non-empty-string, RuleDefinition> $originals */
            $originals = [];

            $replacements = new RuleReplacements();

            foreach ($context->rules as $rule) {
                $key = $this->createKey($rule);

                if ($key === null) {
                    continue;
                }

                $original = $originals[$key] ?? null;

                if ($original === null) {
                    $originals[$key] = $rule;

                    continue;
                }

                $replacements->replace($rule, $original);
            }

            $replacements->applyTo($context);
        } while (!$replacements->isEmpty);
    }

    /**
     * Returns the description of what the rule recognizes, or {@see null} in
     * case of the rule cannot be merged.
     *
     * @return non-empty-string|null
     */
    private function createKey(RuleDefinition $rule): ?string
    {
        // A rule with a reducer builds a node of its own, so it may not be
        // merged
        if ($rule->reducer !== null) {
            return null;
        }

        return match (true) {
            $rule instanceof TokenIdRuleDefinition => \sprintf(
                'id(%d,%s)',
                $rule->tokenId,
                $this->printOccurrence($rule->isKept),
            ),
            $rule instanceof TokenNameRuleDefinition => \sprintf(
                'name(%s,%s)',
                $rule->tokenName,
                $this->printOccurrence($rule->isKept),
            ),
            $rule instanceof TokenRuleDefinition => \sprintf(
                'token(%d,%s)',
                \spl_object_id($rule->token),
                $this->printOccurrence($rule->isKept),
            ),
            $rule instanceof ConcatenationRuleDefinition => \sprintf(
                'concat(%s)',
                $this->printChildren($rule->rules),
            ),
            $rule instanceof AlternationRuleDefinition => \sprintf(
                'choice(%s)',
                $this->printChildren($rule->rules),
            ),
            $rule instanceof OptionalRuleDefinition => \sprintf(
                'optional(%d)',
                \spl_object_id($rule->rule),
            ),
            $rule instanceof PredicateRuleDefinition => \sprintf(
                'predicate(%d,%s)',
                \spl_object_id($rule->rule),
                $rule->isExpected ? 'expect' : 'reject',
            ),
            $rule instanceof RepetitionRuleDefinition => \sprintf(
                'repeat(%d,%d,%s)',
                \spl_object_id($rule->rule),
                $rule->min,
                $this->printOccurrences($rule->max),
            ),
            default => null,
        };
    }

    /**
     * @return non-empty-string
     */
    private function printOccurrence(bool $isKept): string
    {
        return $isKept ? 'keep' : 'skip';
    }

    /**
     * @param int<0, max>|float $max
     */
    private function printOccurrences(int|float $max): string
    {
        if (\is_infinite($max)) {
            return 'inf';
        }

        return (string) $max;
    }

    /**
     * @param list<RuleDefinition> $rules
     */
    private function printChildren(array $rules): string
    {
        $result = [];

        foreach ($rules as $rule) {
            $result[] = \spl_object_id($rule);
        }

        return \implode(',', $result);
    }
}
