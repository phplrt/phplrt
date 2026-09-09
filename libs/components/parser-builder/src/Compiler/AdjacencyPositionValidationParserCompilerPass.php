<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\AdjacencyRuleDefinition;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;

/**
 * Checks that every requirement of adjacency has a token behind it.
 *
 * Such a rule answers whether the token behind it ends where the token ahead of
 * it begins, so it describes a pair. The token ahead is always there, the end
 * of the input being a token of its own, while the one behind is only there
 * once something before the rule has read it: written where nothing has, the
 * rule would compare the input against whatever happens to stand there, which
 * is a token belonging to somebody else.
 *
 * A statement that may be recognized without reading a token is the same case,
 * only found out later: the pair it belongs to exists in the grammar and is
 * gone from the input.
 *
 * Nothing is asked about what follows the rule. A sequence ending with one
 * describes the pair its own last token makes with whatever comes next, which
 * is how a repetition says that its every turn is written next to the one
 * before it.
 *
 * @readonly
 */
final class AdjacencyPositionValidationParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        $adjacent = [];

        foreach ($context->rules as $rule) {
            if ($rule instanceof AdjacencyRuleDefinition) {
                $adjacent[] = $rule;
            }
        }

        if ($adjacent === []) {
            return;
        }

        $parents = RuleParents::createFromRules($context->rules);
        $nullable = NullableRules::createFromRules($context->rules);

        foreach ($adjacent as $rule) {
            $this->validateOrFail($rule, $parents, $nullable);
        }
    }

    /**
     * @throws CompilationFailedException
     */
    private function validateOrFail(
        AdjacencyRuleDefinition $rule,
        RuleParents $parents,
        NullableRules $nullable,
    ): void {
        $referrers = $parents->findParents($rule);

        if ($referrers === []) {
            throw new CompilationFailedException($rule, \sprintf(
                'Rule %s describes a pair of tokens, so it cannot be a rule of its own',
                $rule,
            ));
        }

        foreach ($referrers as $referrer) {
            if (!$referrer instanceof ConcatenationRuleDefinition) {
                throw new CompilationFailedException($rule, \sprintf(
                    'Rule %s describes a pair of tokens, so it may only be written after '
                        . 'a statement of a sequence, and %s is not one',
                    $rule,
                    $referrer->printReference(),
                ));
            }

            $this->validatePrecedingOrFail($rule, $referrer, $nullable);
        }
    }

    /**
     * @throws CompilationFailedException
     */
    private function validatePrecedingOrFail(
        AdjacencyRuleDefinition $rule,
        ConcatenationRuleDefinition $referrer,
        NullableRules $nullable,
    ): void {
        foreach ($referrer->rules as $index => $statement) {
            if ($statement !== $rule) {
                continue;
            }

            if ($index === 0) {
                throw new CompilationFailedException($rule, \sprintf(
                    'Rule %s compares the token behind it, so it cannot open the sequence %s: '
                        . 'the token it would compare belongs to whatever stands before the rule',
                    $rule,
                    $referrer->printReference(),
                ));
            }

            $this->validateNeighbourOrFail($rule, $referrer, $referrer->rules[$index - 1], $nullable);
        }
    }

    /**
     * @throws CompilationFailedException
     */
    private function validateNeighbourOrFail(
        AdjacencyRuleDefinition $rule,
        ConcatenationRuleDefinition $referrer,
        RuleDefinition $neighbour,
        NullableRules $nullable,
    ): void {
        if (!$nullable->isNullable($neighbour)) {
            return;
        }

        throw new CompilationFailedException($rule, \sprintf(
            'Rule %s compares the token behind it, but %s of the sequence %s may be recognized '
                . 'without reading one, so there may be no such token in the sequence at all. '
                . 'Write an alternative per case instead, so that every case names the tokens '
                . 'it compares',
            $rule,
            $neighbour->printReference(),
            $referrer->printReference(),
        ));
    }
}
