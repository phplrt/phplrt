<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder;

use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Analysis\BranchPredictionConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\LookaheadConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\ParserAnalysisPassInterface;
use Phplrt\Parser\Builder\Analysis\ParserResultContext;
use Phplrt\Parser\Builder\Analysis\TreePresenceConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Compiler\DuplicateRuleParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\InitialRuleParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\LeftRecursionValidationParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\NestedConcatenationParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\NestedRepetitionParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\ParserBuildingContext;
use Phplrt\Parser\Builder\Compiler\ParserCompilerPassInterface;
use Phplrt\Parser\Builder\Compiler\ProductionValidationParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\RedundantProductionParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\RepeatedAlternativeParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\RuleNameDuplicationParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\RuleReferenceResolutionParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\TokenReferenceValidationParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\UnreachableRuleParserCompilerPass;
use Phplrt\Parser\Builder\Definition\AlternationRuleDefinition;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\OptionalRuleDefinition;
use Phplrt\Parser\Builder\Definition\PredicateRuleDefinition;
use Phplrt\Parser\Builder\Definition\RepetitionRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleReference;
use Phplrt\Parser\Builder\Definition\TerminalRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenIdRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenNameRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenRuleDefinition;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Builder\Transformer\ParserBuilderResultTransformer;
use Phplrt\Parser\Builder\Transformer\ParserBuildingContextTransformer;
use Phplrt\Parser\Builder\Transformer\ParserResultContextTransformer;

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
                new NestedConcatenationParserCompilerPass(),
                new NestedRepetitionParserCompilerPass(),
                /**
                 * The rules recognizing the same input become the very same
                 * rule here, so the alternatives repeating each other are only
                 * told apart afterwards.
                 */
                new DuplicateRuleParserCompilerPass(),
                new RepeatedAlternativeParserCompilerPass(),
            ],
        ];

        $this->analysisPasses = [
            new LookaheadConstructionParserAnalysisPass(),
            new TreePresenceConstructionParserAnalysisPass(),
            /**
             * Which alternative may be entered is decided by the tokens a rule
             * begins with, so the alternations are answered for only once those
             * are known.
             */
            new BranchPredictionConstructionParserAnalysisPass(),
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
     * Adds the rule looking at what comes next without reading it.
     *
     * The given rule is recognized the very same way as any other, but nothing
     * it has recognized is kept: the input stays where it was and the result
     * gets nothing, so the only thing left is whether it has matched.
     *
     * For example,
     * ```php
     * // A name that is not followed by a parenthesis
     * $parser->addConcatenation([
     *     $parser->addPredicate($parser->addTokenReference('T_PARENTHESIS_OPEN'), isExpected: false),
     *     $parser->addTokenReference('T_NAME'),
     * ]);
     * ```
     *
     * @api
     *
     * @param non-empty-string|null $name
     */
    public function addPredicate(
        RuleDefinition $rule,
        bool $isExpected = true,
        ?string $name = null,
    ): PredicateRuleDefinition {
        $definition = new PredicateRuleDefinition($rule, $isExpected, $name);

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
     * Removes every pass of the given class, whatever priority it has been
     * registered with.
     *
     * A pass is named rather than given, because the one to remove has been
     * registered by somebody else: the passes the builder starts with are
     * built by the builder itself.
     *
     * @api
     *
     * @param class-string<ParserCompilerPassInterface> $class
     * @return $this
     */
    public function removeCompilerPass(string $class): self
    {
        foreach ($this->compilerPasses as $priority => $passes) {
            $remaining = [];

            foreach ($passes as $pass) {
                if (!$pass instanceof $class) {
                    $remaining[] = $pass;
                }
            }

            $this->compilerPasses[$priority] = $remaining;
        }

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
