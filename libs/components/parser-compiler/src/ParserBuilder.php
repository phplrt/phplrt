<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Analysis\LookaheadConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Analysis\ParserAnalysisPassInterface;
use Phplrt\Compiler\Parser\Analysis\ParserResultContext;
use Phplrt\Compiler\Parser\Analysis\TreePresenceConstructionParserAnalysisPass;
use Phplrt\Compiler\Parser\Compiler\DuplicateRuleParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\InitialRuleParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\LeftRecursionValidationParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\NestedProductionParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\ParserBuildingContext;
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
use Phplrt\Compiler\Parser\Exception\ParserCompilerException;
use Phplrt\Compiler\Parser\Transformer\ParserBuilderResultTransformer;
use Phplrt\Compiler\Parser\Transformer\ParserBuildingContextTransformer;
use Phplrt\Compiler\Parser\Transformer\ParserResultContextTransformer;

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
     * Contains the rule the analysis starts at, or {@see null} in case of the
     * first rule added to the builder is used
     */
    public private(set) ?RuleDefinition $initial = null;

    /**
     * The rules added to the builder.
     *
     * A reference may point at such a rule by name, and a rule that has been
     * added is a part of the grammar even before another one refers to it.
     *
     * @var \SplObjectStorage<RuleDefinition, null>
     */
    public private(set) \SplObjectStorage $rules {
        get => $this->rules ??= new \SplObjectStorage();
    }

    /**
     * The passes rewriting and checking the rules, indexed by their priority.
     *
     * @var array<int, list<ParserCompilerPassInterface>>
     */
    public private(set) array $compilerPasses = [];

    /**
     * The passes describing the assembled grammar, in the order they have been
     * registered.
     *
     * @var list<ParserAnalysisPassInterface>
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
            new LookaheadConstructionParserAnalysisPass(),
            new TreePresenceConstructionParserAnalysisPass(),
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
        $this->rules->offsetSet($definition);

        return $this;
    }

    /**
     * @api
     *
     * @return $this
     */
    public function removeRule(RuleDefinition $definition): self
    {
        $this->rules->offsetUnset($definition);

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
     * @param (self::PASS_PRIORITY_*|int) $priority
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
     * The analysis passes read the very same grammar and write the metadata of
     * their own, so they are processed in the order they have been registered.
     *
     * @api
     *
     * @return $this
     */
    public function addAnalysisPass(ParserAnalysisPassInterface $pass): self
    {
        $this->analysisPasses[] = $pass;

        return $this;
    }

    /**
     * @throws ParserCompilerException
     */
    public function build(LexerBuilderResult $lexer): ParserBuilderResult
    {
        $building = new ParserBuildingContextTransformer()->transform($this);

        $this->process($building, $lexer);

        $result = new ParserResultContextTransformer()->transform($building, $lexer);

        $this->analyze($result);

        return new ParserBuilderResultTransformer()->transform($result);
    }

    /**
     * @throws ParserCompilerException
     */
    private function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
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
    }

    /**
     * @throws ParserCompilerException
     */
    private function analyze(ParserResultContext $context): void
    {
        try {
            foreach ($this->analysisPasses as $pass) {
                $pass->process($context);
            }
        } catch (ParserCompilerException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ParserCompilerException::becauseInternalErrorOccurs($e);
        }
    }
}
