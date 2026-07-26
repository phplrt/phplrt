<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Transformer;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Analysis\ParserResultContext;
use Phplrt\Compiler\Parser\Compiler\ParserBuildingContext;
use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\OptionalRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RepetitionRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\Definition\TerminalRuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenIdRuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenNameRuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenRuleDefinition;
use Phplrt\Compiler\Parser\Exception\CompilationFailedException;
use Phplrt\Compiler\Parser\Exception\ParserCompilerException;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
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
        );
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
                rules: $this->createReferences($definition, $definition->rules, $identifiers),
            ),
            $definition instanceof AlternationRuleDefinition => new Alternation(
                ruleIds: $this->createReferences($definition, $definition->rules, $identifiers),
            ),
            $definition instanceof OptionalRuleDefinition => new Optional(
                ruleId: $identifiers[$definition->rule],
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
