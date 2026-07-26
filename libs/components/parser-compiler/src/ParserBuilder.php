<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Analysis\KeptConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Analysis\LookaheadConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Analysis\ParserAnalysis;
use Phplrt\Compiler\Parser\Analysis\ParserAnalysisPassInterface;
use Phplrt\Compiler\Parser\Compiler\DuplicateRuleParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\InitialRuleParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\LeftRecursionValidationParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\NestedProductionParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\ParserCompilerPassInterface;
use Phplrt\Compiler\Parser\Compiler\ProductionValidationParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\RedundantProductionParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\RuleNameDuplicationParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\RuleReferenceResolutionParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\TokenReferenceValidationParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\UnreachableRuleParserCompilerPass;
use Phplrt\Compiler\Parser\Definition\AlternationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\ConcatenationRuleDefinition;
use Phplrt\Compiler\Parser\Definition\OptionalRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RepetitionRuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleDefinition;
use Phplrt\Compiler\Parser\Definition\RuleReference;
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

final class ParserBuilder
{
    /**
     * Brings the grammar to the form the rest of the passes expect: the
     * references are replaced by the rules they point at and the rules that
     * cannot be reached are dropped.
     */
    public const int PASS_PRIORITY_NORMALIZE = 0;

    /**
     * Reports the grammar that cannot be compiled into a parser.
     */
    public const int PASS_PRIORITY_CHECK = 100;

    /**
     * Rewrites the grammar, keeping the input it recognizes and the result it
     * builds the same.
     */
    public const int PASS_PRIORITY_OPTIMIZE = 200;

    /**
     * Reports the grammar that has been broken by a rewrite.
     */
    public const int PASS_PRIORITY_CHECK_AFTER_OPTIMIZE = 300;

    /**
     * Describes the assembled grammar for the parser to analyse the input
     * without walking the rules that cannot match it.
     *
     * The rules are assigned their identifiers before this step, so the passes
     * of this priority are {@see ParserAnalysisPassInterface} ones.
     */
    public const int PASS_PRIORITY_ANALYZE = 400;

    /**
     * Contains the rule the analysis starts at, or {@see null} in case of the
     * first rule added to the builder is used
     */
    public private(set) ?RuleDefinition $initial = null;

    /**
     * The rules of the grammar, in the order they are reached starting from
     * the initial one.
     *
     * @var list<RuleDefinition>
     */
    public array $rules {
        get => $this->collectRules();
    }

    /**
     * The rules added to the builder.
     *
     * A reference may point at such a rule by name, and a rule that has been
     * added is a part of the grammar even before another one refers to it.
     *
     * @var \SplObjectStorage<RuleDefinition, null>
     */
    public private(set) \SplObjectStorage $declarations {
        get => $this->declarations ??= new \SplObjectStorage();
    }

    /**
     * The passes rewriting and checking the rules, indexed by their priority.
     *
     * @var array<int, list<ParserCompilerPassInterface>>
     */
    public private(set) array $compilerPasses = [];

    /**
     * The passes describing the assembled grammar, indexed by their priority.
     *
     * @var array<int, list<ParserAnalysisPassInterface>>
     */
    public private(set) array $analysisPasses = [];

    public function __construct()
    {
        $this->compilerPasses = [
            self::PASS_PRIORITY_NORMALIZE => [
                /**
                 * Everything below needs to know where the grammar starts.
                 */
                new InitialRuleParserCompilerPass(),
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
            ],
            self::PASS_PRIORITY_CHECK => [
                new TokenReferenceValidationParserCompilerPass(),
                new ProductionValidationParserCompilerPass(),
                new LeftRecursionValidationParserCompilerPass(),
            ],
            self::PASS_PRIORITY_OPTIMIZE => [
                new RedundantProductionParserCompilerPass(),
                new NestedProductionParserCompilerPass(),
                new DuplicateRuleParserCompilerPass(),
            ],
        ];

        $this->analysisPasses = [
            self::PASS_PRIORITY_ANALYZE => [
                new LookaheadConstructionParserAnalysisPass(),
                new KeptConstructionParserAnalysisPass(),
            ],
        ];
    }

    /**
     * Marks the rule the analysis starts at.
     *
     * @api
     *
     * @return $this
     */
    public function setInitialRule(RuleDefinition $rule): self
    {
        $this->initial = $rule;

        return $this;
    }

    /**
     * @return list<RuleDefinition>
     */
    private function collectRules(): array
    {
        /** @var \SplObjectStorage<RuleDefinition, null> $visited */
        $visited = new \SplObjectStorage();
        $result = [];

        if ($this->initial !== null) {
            $this->collectRule($this->initial, $visited, $result);
        }

        foreach ($this->declarations as $declaration) {
            $this->collectRule($declaration, $visited, $result);
        }

        return $result;
    }

    /**
     * @param \SplObjectStorage<RuleDefinition, null> $visited
     * @param list<RuleDefinition> $result
     */
    private function collectRule(RuleDefinition $rule, \SplObjectStorage $visited, array &$result): void
    {
        if ($visited->offsetExists($rule)) {
            return;
        }

        $visited->offsetSet($rule);

        $result[] = $rule;

        foreach ($rule->children as $child) {
            $this->collectRule($child, $visited, $result);
        }
    }

    /**
     * Adds the rule recognizing the given token of the lexer.
     *
     * The token is addressed either by its definition, by its name or by its
     * identifier.
     *
     * @api
     *
     * @param TokenDefinition|non-empty-string|int $token
     * @param non-empty-string|null $name the name of the rule being added
     */
    public function addTokenReference(
        TokenDefinition|string|int $token,
        ?string $name = null,
    ): TerminalRuleDefinition {
        $rule = match (true) {
            $token instanceof TokenDefinition => new TokenRuleDefinition($token, $name),
            \is_int($token) => new TokenIdRuleDefinition($token, $name),
            default => new TokenNameRuleDefinition($token, $name),
        };

        $this->addRule($rule);

        return $rule;
    }

    /**
     * Adds the rule recognizing all the given rules, one after another.
     *
     * @api
     *
     * @param list<RuleDefinition> $rules
     * @param non-empty-string|null $name
     */
    public function addConcatenation(array $rules = [], ?string $name = null): ConcatenationRuleDefinition
    {
        $rule = new ConcatenationRuleDefinition($rules, $name);

        $this->addRule($rule);

        return $rule;
    }

    /**
     * Adds the rule recognizing the first of the given rules that matches the
     * input.
     *
     * @api
     *
     * @param list<RuleDefinition> $rules
     * @param non-empty-string|null $name
     */
    public function addAlternation(array $rules = [], ?string $name = null): AlternationRuleDefinition
    {
        $rule = new AlternationRuleDefinition($rules, $name);

        $this->addRule($rule);

        return $rule;
    }

    /**
     * Adds the rule recognizing the given rule, if the input matches it.
     *
     * @api
     *
     * @param non-empty-string|null $name
     */
    public function addOptional(RuleDefinition $rule, ?string $name = null): OptionalRuleDefinition
    {
        $definition = new OptionalRuleDefinition($rule, $name);

        $this->addRule($definition);

        return $definition;
    }

    /**
     * Adds the rule recognizing the given rule as many times as the input
     * matches it.
     *
     * @api
     *
     * @param int<0, max> $min
     * @param int<0, max>|float $max
     * @param non-empty-string|null $name
     */
    public function addRepetition(
        RuleDefinition $rule,
        int|float $max = \INF,
        int $min = 0,
        ?string $name = null,
    ): RepetitionRuleDefinition {
        $definition = new RepetitionRuleDefinition($rule, $min, $max, $name);

        $this->addRule($definition);

        return $definition;
    }

    /**
     * Adds the reference to another rule of the grammar, addressed either by
     * its definition or by its name.
     *
     * A reference stands for the rule until the parser is compiled: it is
     * replaced by the rule it points at, so the grammar never contains one.
     *
     * @api
     *
     * @param RuleDefinition|non-empty-string $rule
     */
    public function addRuleReference(RuleDefinition|string $rule): RuleReference
    {
        $reference = new RuleReference($rule);

        $this->addRule($reference);

        return $reference;
    }

    /**
     * Adds the rule to the builder, so that a reference may point at it by
     * name and the grammar may start at it.
     *
     * @api
     *
     * @return $this
     */
    public function addRule(RuleDefinition $definition): self
    {
        $this->declarations->offsetSet($definition);

        return $this;
    }

    /**
     * @api
     *
     * @return $this
     */
    public function removeRule(RuleDefinition $definition): self
    {
        $this->declarations->offsetUnset($definition);

        return $this;
    }

    /**
     * Registers the pass rewriting or checking the rules of the grammar.
     *
     * The passes of the same priority are processed in the order they have
     * been registered.
     *
     * @api
     *
     * @return $this
     */
    public function addCompilerPass(
        ParserCompilerPassInterface $pass,
        int $priority = self::PASS_PRIORITY_CHECK,
    ): self {
        $this->compilerPasses[$priority][] = $pass;

        \ksort($this->compilerPasses);

        return $this;
    }

    /**
     * Registers the pass describing the assembled grammar.
     *
     * The passes of the same priority are processed in the order they have
     * been registered.
     *
     * @api
     *
     * @return $this
     */
    public function addAnalysisPass(
        ParserAnalysisPassInterface $pass,
        int $priority = self::PASS_PRIORITY_ANALYZE,
    ): self {
        $this->analysisPasses[$priority][] = $pass;

        \ksort($this->analysisPasses);

        return $this;
    }

    /**
     * @throws ParserCompilerException
     */
    public function build(LexerBuilderResult $lexer): ParserBuilderResult
    {
        $context = $this->process($lexer);

        $initial = $context->initial;

        if ($initial === null) {
            throw new ParserCompilerException('The grammar of the parser contains no rules');
        }

        $definitions = $context->rules;

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
    private function findTokenId(TerminalRuleDefinition $definition, LexerBuilderResult $lexer): int
    {
        $id = match (true) {
            $definition instanceof TokenIdRuleDefinition => $definition->tokenId,
            $definition instanceof TokenNameRuleDefinition => $lexer->constants[$definition->tokenName] ?? null,
            $definition instanceof TokenRuleDefinition => $lexer->findTokenId($definition->token),
        };

        return $id ?? throw CompilationFailedException::becauseTokenIsUnknown($definition);
    }

    public function __clone(): void
    {
        $this->declarations = clone $this->declarations;
    }
}
