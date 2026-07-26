<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RepetitionRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\Exception\CompilationFailedException;
use Phplrt\Compiler\Parser\ParserBuilder;

/**
 * Checks that the productions of the grammar are complete.
 */
final readonly class ProductionValidationParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void
    {
        foreach ($builder->rules as $rule) {
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
