<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Analysis\KeptConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Analysis\LookaheadConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Analysis\ParserAnalysis;
use Phplrt\Compiler\Parser\Analysis\ParserAnalysisPassInterface;
use Phplrt\Compiler\Parser\Builder\HasRuleDefinitions;
use Phplrt\Compiler\Parser\Compiler\LeftRecursionValidationParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\ParserCompilerPassInterface;
use Phplrt\Compiler\Parser\Compiler\ProductionValidationParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\RuleNameDuplicationParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\RuleReferenceResolutionParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\RuleReferenceValidationParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\TokenReferenceValidationParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\UnreachableRuleParserCompilerPass;
use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\OptionalRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RepetitionRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenIdRuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenNameRuleDefinition;
use Phplrt\Compiler\Parser\Exception\CompilationFailedException;
use Phplrt\Compiler\Parser\Exception\ParserCompilerException;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;

final class ParserBuilder
{
    use HasRuleDefinitions;

    /**
     * Contains the rule the analysis starts at, or {@see null} in case of the
     * first rule of the grammar is used
     */
    public private(set) ?RuleDefinition $initial = null;

    /**
     * @var array<array-key, list<ParserCompilerPassInterface>>
     */
    public private(set) array $compilerPasses = [];

    /**
     * @var array<array-key, list<ParserAnalysisPassInterface>>
     */
    public private(set) array $analysisPasses = [];

    public function __construct()
    {
        $this->compilerPasses = [
            0 => [
                /**
                 * The references are resolved by name, so the names must be
                 * unambiguous before that.
                 */
                new RuleNameDuplicationParserCompilerPass(),
                new RuleReferenceResolutionParserCompilerPass(),
                /**
                 * Dead rules are dropped next, so that the code that could
                 * never be reached does not produce compilation errors.
                 */
                new UnreachableRuleParserCompilerPass(),
                new RuleReferenceValidationParserCompilerPass(),
                new TokenReferenceValidationParserCompilerPass(),
                new ProductionValidationParserCompilerPass(),
                new LeftRecursionValidationParserCompilerPass(),
            ],
        ];

        $this->analysisPasses = [
            0 => [
                new LookaheadConstructionParserAnalysisPass(),
                new KeptConstructionParserAnalysisPass(),
            ],
        ];
    }

    /**
     * Marks the rule the analysis starts at, registering it in case of it has
     * not been registered yet.
     *
     * @api
     *
     * @return $this
     */
    public function setInitialRule(RuleDefinition $rule): self
    {
        $this->addRule($rule);

        $this->initial = $rule;

        return $this;
    }

    /**
     * Returns the rule the analysis starts at, or {@see null} in case of the
     * grammar contains no rules at all.
     *
     * The first rule of the grammar is used, unless another one has been
     * marked as the initial.
     */
    public function findInitialRule(): ?RuleDefinition
    {
        if ($this->initial !== null) {
            return $this->initial;
        }

        foreach ($this->rules as $rule) {
            return $rule;
        }

        return null;
    }

    /**
     * @return $this
     */
    public function addCompilerPass(ParserCompilerPassInterface $pass, int $priority = 0): self
    {
        $this->compilerPasses[$priority][] = $pass;

        return $this;
    }

    /**
     * @return $this
     */
    public function addAnalysisPass(ParserAnalysisPassInterface $pass, int $priority = 0): self
    {
        $this->analysisPasses[$priority][] = $pass;

        return $this;
    }

    /**
     * @throws ParserCompilerException
     */
    public function build(LexerBuilderResult $lexer): ParserBuilderResult
    {
        $context = $this->process($lexer);

        $initial = $context->findInitialRule();

        if ($initial === null) {
            throw new ParserCompilerException('The grammar of the parser contains no rules');
        }

        /** @var list<RuleDefinition> $definitions */
        $definitions = \iterator_to_array($context->rules, false);

        /** @var \SplObjectStorage<RuleDefinition, int> $identifiers */
        $identifiers = new \SplObjectStorage();
        $initialId = null;

        foreach ($definitions as $id => $definition) {
            $identifiers[$definition] = $id;

            if ($definition === $initial) {
                $initialId = $id;
            }
        }

        if ($initialId === null) {
            throw new CompilationFailedException($initial, \sprintf(
                'Rule %s the analysis starts at is not defined in the grammar',
                $initial,
            ));
        }

        $grammar = [];
        $reducers = [];
        $constants = [];

        foreach ($definitions as $id => $definition) {
            $grammar[] = $this->createRule($definition, $identifiers, $lexer);

            if ($definition->reducer !== null) {
                $reducers[$id] = $definition->reducer;
            }

            if ($definition->name !== null) {
                $constants[$definition->name] = $id;
            }
        }

        $analysis = $this->analyze(new ParserAnalysis(
            grammar: $grammar,
            initial: $initialId,
            reducers: $reducers,
        ));

        return new ParserBuilderResult(
            grammar: $analysis->grammar,
            initial: $analysis->initial,
            first: $analysis->first,
            nullable: $analysis->nullable,
            kept: $analysis->kept,
            reducers: $analysis->reducers,
            constants: $constants,
        );
    }

    /**
     * @throws ParserCompilerException
     */
    private function process(LexerBuilderResult $lexer): self
    {
        $context = clone $this;

        try {
            foreach ($this->compilerPasses as $passes) {
                foreach ($passes as $pass) {
                    $pass->process($context, $lexer);
                }
            }
        } catch (ParserCompilerException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ParserCompilerException::becauseInternalErrorOccurs($e);
        }

        return $context;
    }

    /**
     * @throws ParserCompilerException
     */
    private function analyze(ParserAnalysis $analysis): ParserAnalysis
    {
        try {
            foreach ($this->analysisPasses as $passes) {
                foreach ($passes as $pass) {
                    $pass->process($analysis);
                }
            }
        } catch (ParserCompilerException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ParserCompilerException::becauseInternalErrorOccurs($e);
        }

        return $analysis;
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, int> $identifiers
     * @throws CompilationFailedException
     */
    private function createRule(
        RuleDefinition $definition,
        \SplObjectStorage $identifiers,
        LexerBuilderResult $lexer,
    ): RuleInterface {
        return match (true) {
            $definition instanceof TokenIdRuleDefinition => new Lexeme(
                tokenId: $definition->tokenId,
                keep: $definition->isKept,
            ),
            $definition instanceof TokenNameRuleDefinition => new Lexeme(
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
     * @param \SplObjectStorage<RuleDefinition, int> $identifiers
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
    private function findTokenId(TokenNameRuleDefinition $definition, LexerBuilderResult $lexer): int
    {
        return $lexer->constants[$definition->tokenName]
            ?? throw CompilationFailedException::becauseTokenIsUnknown($definition);
    }

    public function __clone(): void
    {
        $this->rules = clone $this->rules;
    }
}
