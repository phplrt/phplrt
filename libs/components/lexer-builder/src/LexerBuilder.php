<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\Analysis\ChannelConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\LexerAnalysisPassInterface;
use Phplrt\Lexer\Builder\Analysis\LexerResultContext;
use Phplrt\Lexer\Builder\Analysis\RegexConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\TokenNameConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\TransitionConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Builder\HasRegexFlags;
use Phplrt\Lexer\Builder\Builder\HasTokenDefinitions;
use Phplrt\Lexer\Builder\Builder\TokenDefinitionGroup;
use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Compiler\LexerCompilerPassInterface;
use Phplrt\Lexer\Builder\Compiler\RegexDuplicationLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\RegexExcessiveGreedLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\RegexValidationLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\TokenNameDuplicationLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\TokenNameValidationLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\TransitionValidationLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\UnreachableStateLexerCompilerPass;
use Phplrt\Lexer\Builder\Definition\Lexer\EmbeddedLexerInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\RuntimeEmbeddedLexer;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Exception\LexerCompilerException;
use Phplrt\Lexer\Builder\Transformer\LexerBuilderResultTransformer;
use Phplrt\Lexer\Builder\Transformer\LexerBuildingContextTransformer;
use Phplrt\Lexer\Builder\Transformer\LexerResultContextTransformer;

final class LexerBuilder
{
    use HasTokenDefinitions;
    use HasRegexFlags;

    /**
     * Brings the lexer to the form the rest of the passes expect: the states
     * that cannot be entered are dropped.
     */
    public const int PASS_PRIORITY_NORMALIZE = 0;

    /**
     * Reports the lexer that cannot be compiled.
     */
    public const int PASS_PRIORITY_CHECK = 100;

    /**
     * Rewrites the token definitions, keeping the input they recognize the
     * same.
     */
    public const int PASS_PRIORITY_OPTIMIZE = 200;

    /**
     * Reports the lexer that has been broken by a rewrite.
     */
    public const int PASS_PRIORITY_CHECK_AFTER_OPTIMIZE = 300;

    /**
     * @var array<non-empty-string, TokenDefinitionGroup>
     */
    public private(set) array $states = [];

    /**
     * A map of state name and the lexer reading it.
     *
     * @var array<non-empty-string, EmbeddedLexerInterface>
     */
    public private(set) array $embeddedStates = [];

    /**
     * The passes rewriting and checking the token definitions, indexed by
     * their priority.
     *
     * @var array<int, list<LexerCompilerPassInterface>>
     */
    public private(set) array $compilerPasses = [];

    /**
     * The passes describing the assembled lexer, in the order they have been
     * registered.
     *
     * @var list<LexerAnalysisPassInterface>
     */
    public private(set) array $analysisPasses = [];

    public function __construct()
    {
        $this->compilerPasses = [
            /**
             * Dead states are dropped first, so that the code that could
             * never be reached does not produce compilation errors.
             */
            self::PASS_PRIORITY_NORMALIZE => [
                new UnreachableStateLexerCompilerPass(),
            ],
            self::PASS_PRIORITY_CHECK => [
                new TokenNameDuplicationLexerCompilerPass(),
                new TokenNameValidationLexerCompilerPass(),
                new RegexDuplicationLexerCompilerPass(),
                new RegexValidationLexerCompilerPass(),
                new RegexExcessiveGreedLexerCompilerPass(),
                new TransitionValidationLexerCompilerPass(),
            ],
        ];

        $this->analysisPasses = [
            new TokenNameConstructionLexerAnalysisPass(),
            new ChannelConstructionLexerAnalysisPass(),
            new TransitionConstructionLexerAnalysisPass(),
            new RegexConstructionLexerAnalysisPass(),
        ];
    }

    /**
     * Adds the lexer state and returns the group its token definitions belong
     * to, or returns the group of the state that has been added earlier.
     *
     * A state can only be reached using a {@see TokenDefinition::enter()}
     * transition and left using a {@see TokenDefinition::exit()} one.
     *
     * For example,
     * ```php
     * $builder->addPattern('"')
     *      ->enter('string');
     * $builder->addState('string')
     *      ->addPattern('"')
     *      ->exit();
     * ```
     *
     * @api
     *
     * @param non-empty-string $name
     */
    public function addState(string $name): TokenDefinitionGroup
    {
        // A state name identifies a single state, whatever it is read by
        unset($this->embeddedStates[$name]);

        return $this->states[$name] ??= new TokenDefinitionGroup();
    }

    /**
     * Adds the lexer state read by a lexer of its own.
     *
     * Such a state has no token definitions: the lexer decides on its own
     * where the fragment it reads ends and returns the control back as soon as
     * it stops.
     *
     * For example,
     * ```php
     * $builder->addPattern('<\?php')
     *      ->enter('php');
     * $builder->addEmbeddedState('php', new PhpTokenLexer());
     * ```
     *
     * @api
     *
     * @param non-empty-string $name
     */
    public function addEmbeddedState(
        string $name,
        EmbeddedLexerInterface|LexerInterface $lexer,
    ): EmbeddedLexerInterface {
        // A state name identifies a single state, whatever it is read by
        unset($this->states[$name]);

        return $this->embeddedStates[$name] = $lexer instanceof LexerInterface
            ? new RuntimeEmbeddedLexer($lexer)
            : $lexer;
    }

    /**
     * Removes the given lexer state along with everything it is read by.
     *
     * @api
     *
     * @param non-empty-string $name
     * @return $this
     */
    public function removeState(string $name): self
    {
        unset($this->states[$name], $this->embeddedStates[$name]);

        return $this;
    }

    /**
     * Registers the pass rewriting or checking the token definitions.
     *
     * The passes of the same priority are processed in the order they have
     * been registered.
     *
     * @api
     *
     * @param (self::PASS_PRIORITY_*|int) $priority
     * @return $this
     */
    public function addCompilerPass(LexerCompilerPassInterface $pass, int $priority = self::PASS_PRIORITY_CHECK): self
    {
        $this->compilerPasses[$priority][] = $pass;

        \ksort($this->compilerPasses);

        return $this;
    }

    /**
     * Registers the pass describing the assembled lexer.
     *
     * The analysis passes read the very same lexer and write the metadata of
     * their own, so they are processed in the order they have been registered.
     *
     * @api
     *
     * @return $this
     */
    public function addAnalysisPass(LexerAnalysisPassInterface $pass): self
    {
        $this->analysisPasses[] = $pass;

        return $this;
    }

    /**
     * @throws LexerCompilerException
     */
    public function build(): LexerBuilderResult
    {
        $building = new LexerBuildingContextTransformer()
            ->transform($this);

        $this->process($building);

        $result = new LexerResultContextTransformer()
            ->transform($building);

        $this->analyze($result);

        return new LexerBuilderResultTransformer()
            ->transform($result);
    }

    /**
     * @throws LexerCompilerException
     */
    private function process(LexerBuildingContext $context): void
    {
        try {
            foreach ($this->compilerPasses as $passes) {
                foreach ($passes as $pass) {
                    $pass->process($context);
                }
            }
        } catch (LexerCompilerException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw LexerCompilerException::becauseInternalErrorOccurs($e);
        }
    }

    /**
     * @throws LexerCompilerException
     */
    private function analyze(LexerResultContext $context): void
    {
        try {
            foreach ($this->analysisPasses as $pass) {
                $pass->process($context);
            }
        } catch (LexerCompilerException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw LexerCompilerException::becauseInternalErrorOccurs($e);
        }
    }
}
