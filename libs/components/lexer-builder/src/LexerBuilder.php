<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\Analysis\ChannelConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\LexerAnalysisPassInterface;
use Phplrt\Lexer\Builder\Analysis\LexerResultContext;
use Phplrt\Lexer\Builder\Analysis\RegexConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\SubgroupConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\TokenNameConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\TransitionConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Compiler\LexerCompilerPassInterface;
use Phplrt\Lexer\Builder\Compiler\RegexDuplicationLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\RegexExcessiveGreedLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\RegexValidationLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\TokenNameDuplicationLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\TokenNameValidationLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\TransitionValidationLexerCompilerPass;
use Phplrt\Lexer\Builder\Compiler\UnreachableLexerCompilerPass;
use Phplrt\Lexer\Builder\Definition\Lexer\EmbeddedLexerInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\RuntimeEmbeddedLexer;
use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Definition\ValueTokenDefinition;
use Phplrt\Lexer\Builder\Exception\LexerCompilerException;
use Phplrt\Lexer\Builder\Transformer\LexerBuilderResultTransformer;
use Phplrt\Lexer\Builder\Transformer\LexerBuildingContextTransformer;
use Phplrt\Lexer\Builder\Transformer\LexerResultContextTransformer;

final class LexerBuilder
{
    /**
     * Brings the lexer to the form the rest of the passes expect: the lexers
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
     * Contains {@see true} in case of the lexer is called by another one, so
     * it is allowed to stop reading and give the control back
     */
    public private(set) bool $isEmbedded = false;

    /**
     * The token definitions the lexer recognizes on its own, in the order they
     * are tried.
     *
     * @var array<array-key, TokenDefinition>
     */
    public private(set) array $tokens = [];

    /**
     * A map of modifier value and the modifier the pattern is compiled with.
     *
     * @var array<non-empty-string, RegexModifier>
     */
    public private(set) array $flags = [
        RegexModifier::Compiled->value => RegexModifier::Compiled,
        RegexModifier::DotAll->value => RegexModifier::DotAll,
        RegexModifier::Utf8->value => RegexModifier::Utf8,
        RegexModifier::Multiline->value => RegexModifier::Multiline,
    ];

    /**
     * A map of name and the lexer reading the fragment it stands for.
     *
     * @var array<non-empty-string, self|EmbeddedLexerInterface>
     */
    public private(set) array $lexers = [];

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
             * Dead lexers are dropped first, so that the code that could
             * never be reached does not produce compilation errors.
             */
            self::PASS_PRIORITY_NORMALIZE => [
                new UnreachableLexerCompilerPass(),
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
            new SubgroupConstructionLexerAnalysisPass(),
            new RegexConstructionLexerAnalysisPass(),
        ];
    }

    /**
     * Adds the token recognized by the given regular expression.
     *
     * @api
     *
     * @param non-empty-string $pattern
     * @param non-empty-string|null $name
     */
    public function addPattern(string $pattern, ?string $name = null): RegexTokenDefinition
    {
        $definition = new RegexTokenDefinition($pattern, $name);

        $this->addToken($definition);

        return $definition;
    }

    /**
     * Adds the token recognized by the given value, as it is written.
     *
     * @api
     *
     * @param non-empty-string $value
     * @param non-empty-string|null $name
     */
    public function addValue(string $value, ?string $name = null): ValueTokenDefinition
    {
        $definition = new ValueTokenDefinition($value, $name);

        $this->addToken($definition);

        return $definition;
    }

    /**
     * Adds the token definition to the end of the list, so that the ones added
     * earlier are tried first.
     *
     * @api
     *
     * @return $this
     */
    public function addToken(TokenDefinition $definition): self
    {
        $this->removeToken($definition);

        $this->tokens[] = $definition;

        return $this;
    }

    /**
     * @api
     *
     * @return $this
     */
    public function removeToken(TokenDefinition $definition): self
    {
        foreach ($this->tokens as $index => $token) {
            if ($token === $definition) {
                unset($this->tokens[$index]);

                break;
            }
        }

        return $this;
    }

    /**
     * Compiles the pattern of the lexer with the given modifier.
     *
     * @api
     */
    public function enable(RegexModifier $flag): RegexModifier
    {
        return $this->flags[$flag->value] = $flag;
    }

    /**
     * @api
     */
    public function disable(RegexModifier $flag): RegexModifier
    {
        unset($this->flags[$flag->value]);

        return $flag;
    }

    /**
     * Adds the lexer reading a fragment of the source and returns the builder
     * describing it, or returns the builder added earlier under that name.
     *
     * A fragment is read on a {@see TokenDefinition::enter()} transition, and
     * the lexer reading it gives the control back on a
     * {@see TokenDefinition::exit()} one. Everything it reads is carried by
     * the token that entered it, so it never reaches this lexer's own stream.
     *
     * For example,
     * ```php
     * $builder->addPattern('"')
     *      ->enter('string');
     * $builder->addLexer('string')
     *      ->addPattern('"')
     *      ->exit();
     * ```
     *
     * @api
     *
     * @param non-empty-string $name
     */
    public function addLexer(string $name): self
    {
        $lexer = $this->lexers[$name] ?? null;

        if ($lexer instanceof self) {
            return $lexer;
        }

        $lexer = new self();
        $lexer->isEmbedded = true;

        return $this->lexers[$name] = $lexer;
    }

    /**
     * Adds the fragment read by a lexer written by hand.
     *
     * Such a lexer decides on its own where the fragment it reads ends, so it
     * is described by nothing but itself.
     *
     * For example,
     * ```php
     * $builder->addPattern('<\?php')
     *      ->enter('php');
     * $builder->addEmbeddedLexer('php', new PhpTokenLexer());
     * ```
     *
     * @api
     *
     * @param non-empty-string $name
     */
    public function addEmbeddedLexer(
        string $name,
        EmbeddedLexerInterface|LexerInterface $lexer,
    ): EmbeddedLexerInterface {
        return $this->lexers[$name] = $lexer instanceof LexerInterface
            ? new RuntimeEmbeddedLexer($lexer)
            : $lexer;
    }

    /**
     * Removes the lexer added under the given name.
     *
     * @api
     *
     * @param non-empty-string $name
     * @return $this
     */
    public function removeLexer(string $name): self
    {
        unset($this->lexers[$name]);

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
     * Removes every pass of the given class, whatever priority it has been
     * registered with.
     *
     * A pass is named rather than given, because the one to remove has been
     * registered by somebody else: the passes the builder starts with are
     * built by the builder itself.
     *
     * @api
     *
     * @param class-string<LexerCompilerPassInterface> $class
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
