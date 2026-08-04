<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Transformer;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Analysis\ParserResultContext;
use Phplrt\Parser\Builder\Compiler\ParserBuildingContext;
use Phplrt\Parser\Builder\Definition\AlternationRuleDefinition;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\OptionalRuleDefinition;
use Phplrt\Parser\Builder\Definition\PredicateRuleDefinition;
use Phplrt\Parser\Builder\Definition\RepetitionRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\TerminalRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenIdRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenNameRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenRuleDefinition;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Predicate;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Assembles the grammar out of the rules the compiler passes have left behind.
 *
 * Identifiers are assigned here: a rule is addressed by its position in the
 * grammar from now on, and the token a terminal refers to is looked up in the
 * lexer, so this is the point after which the rules may no longer be rewritten.
 */
final readonly class ParserResultContextTransformer
{
    /**
     * @throws ParserCompilerException
     */
    public function transform(ParserBuildingContext $context, LexerBuilderResult $lexer): ParserResultContext
    {
        $initial = $context->initial;

        if ($initial === null) {
            throw new ParserCompilerException('The grammar of the parser contains no rules');
        }

        $identifiers = $this->index($context->rules);

        if (!$identifiers->offsetExists($initial)) {
            throw new CompilationFailedException($initial, \sprintf(
                'Rule %s the analysis starts at is not defined in the grammar',
                $initial,
            ));
        }

        $grammar = [];
        $reducers = [];
        $constants = [];

        foreach ($context->rules as $id => $definition) {
            $grammar[] = $this->createRule($definition, $identifiers, $lexer);

            if ($definition->reducer !== null) {
                $reducers[$id] = $definition->reducer;
            }

            if ($definition->name !== null) {
                $constants[$definition->name] = $id;
            }
        }

        return new ParserResultContext(
            grammar: $grammar,
            initial: $identifiers[$initial],
            reducers: $reducers,
            constants: $constants,
            expectations: $this->createExpectations($lexer),
        );
    }

    /**
     * Describes every token the way an error has to name it.
     *
     * A token that has a name is called by it, and an anonymous one stands for
     * what it is recognized by: a value in quotes or a pattern in slashes,
     * which is exactly what its definition prints.
     *
     * @return array<int, non-empty-string>
     */
    private function createExpectations(LexerBuilderResult $lexer): array
    {
        $result = [];

        foreach ($lexer->tokens as $id => $definition) {
            $result[$id] = $definition->name ?? (string) $definition;
        }

        return $result;
    }

    /**
     * @param list<RuleDefinition> $rules
     * @return \SplObjectStorage<RuleDefinition, int<0, max>>
     */
    private function index(array $rules): \SplObjectStorage
    {
        /** @var \SplObjectStorage<RuleDefinition, int<0, max>> $identifiers */
        $identifiers = new \SplObjectStorage();

        foreach ($rules as $id => $rule) {
            $identifiers->offsetSet($rule, $id);
        }

        return $identifiers;
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, int<0, max>> $identifiers
     * @throws CompilationFailedException
     */
    private function createRule(
        RuleDefinition $definition,
        \SplObjectStorage $identifiers,
        LexerBuilderResult $lexer,
    ): RuleInterface {
        return match (true) {
            $definition instanceof TerminalRuleDefinition => new Lexeme(
                tokenId: $this->findTokenId($definition, $lexer),
                keep: $definition->isKept,
            ),
            $definition instanceof ConcatenationRuleDefinition => new Concatenation(
                ruleIds: $this->createReferences($definition, $definition->rules, $identifiers),
            ),
            $definition instanceof AlternationRuleDefinition => new Alternation(
                ruleIds: $this->createReferences($definition, $definition->rules, $identifiers),
            ),
            $definition instanceof OptionalRuleDefinition => new Optional(
                ruleId: $identifiers[$definition->rule],
            ),
            $definition instanceof PredicateRuleDefinition => new Predicate(
                ruleId: $identifiers[$definition->rule],
                isExpected: $definition->isExpected,
            ),
            $definition instanceof RepetitionRuleDefinition => new Repetition(
                ruleId: $identifiers[$definition->rule],
                min: $definition->min,
                max: $definition->max,
            ),
            default => throw new CompilationFailedException($definition, \sprintf(
                'Unsupported rule definition %s',
                $definition::class,
            )),
        };
    }

    /**
     * @param list<RuleDefinition> $rules
     * @param \SplObjectStorage<RuleDefinition, int<0, max>> $identifiers
     * @return non-empty-list<int>
     * @throws CompilationFailedException
     */
    private function createReferences(
        RuleDefinition $definition,
        array $rules,
        \SplObjectStorage $identifiers,
    ): array {
        $result = [];

        foreach ($rules as $rule) {
            $result[] = $identifiers[$rule];
        }

        if ($result === []) {
            throw CompilationFailedException::becauseRuleIsEmpty($definition);
        }

        return $result;
    }

    /**
     * @throws CompilationFailedException
     */
    private function findTokenId(TerminalRuleDefinition $definition, LexerBuilderResult $lexer): int
    {
        $id = match (true) {
            $definition instanceof TokenIdRuleDefinition => $definition->tokenId,
            $definition instanceof TokenNameRuleDefinition => $this->findTokenIdByName(
                $lexer,
                $definition->tokenName,
            ),
            $definition instanceof TokenRuleDefinition => $lexer->findTokenId($definition->token),
        };

        return $id ?? throw CompilationFailedException::becauseTokenIsUnknown($definition);
    }

    /**
     * @param non-empty-string $name
     */
    private function findTokenIdByName(LexerBuilderResult $lexer, string $name): ?int
    {
        $id = \array_search($name, $lexer->names, true);

        return $id === false ? null : $id;
    }
}
