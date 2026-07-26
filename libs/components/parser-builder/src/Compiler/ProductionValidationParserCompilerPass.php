<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\AlternationRuleDefinition;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\RepetitionRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;

/**
 * Checks that the productions of the grammar are complete.
 */
final readonly class ProductionValidationParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        foreach ($context->rules as $rule) {
            match (true) {
                $rule instanceof ConcatenationRuleDefinition,
                $rule instanceof AlternationRuleDefinition => $this->validateChildrenOrFail($rule),
                $rule instanceof RepetitionRuleDefinition => $this->validateOccurrencesOrFail($rule),
                default => null,
            };
        }
    }

    /**
     * @throws CompilationFailedException
     */
    private function validateChildrenOrFail(RuleDefinition $rule): void
    {
        if ($rule->children !== []) {
            return;
        }

        throw CompilationFailedException::becauseRuleIsEmpty($rule);
    }

    /**
     * @throws CompilationFailedException
     */
    private function validateOccurrencesOrFail(RepetitionRuleDefinition $rule): void
    {
        if ($rule->max >= $rule->min) {
            return;
        }

        throw new CompilationFailedException($rule, \sprintf(
            'Rule %s cannot be repeated from %d to %s times',
            $rule,
            $rule->min,
            (string) $rule->max,
        ));
    }
}
