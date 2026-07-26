<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer;

use Phplrt\Compiler\Lexer\Builder\HasRegexFlags;
use Phplrt\Compiler\Lexer\Builder\HasTokenDefinitions;
use Phplrt\Compiler\Lexer\Builder\TokenDefinitionGroup;
use Phplrt\Compiler\Lexer\Compiler\LexerCompilerPassInterface;
use Phplrt\Compiler\Lexer\Compiler\RegexDuplicationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\RegexExcessiveGreedLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\RegexValidationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\TokenNameDuplicationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\TokenNameValidationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\TransitionValidationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\UnreachableStateLexerCompilerPass;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Exception\LexerCompilerException;
use Phplrt\Compiler\Lexer\Generator\GeneratedResult;
use Phplrt\Compiler\Lexer\Generator\OutputGeneratorInterface;
use Phplrt\Compiler\Lexer\Generator\Phplrt4OutputGenerator;

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
     * The passes rewriting and checking the token definitions, indexed by
     * their priority.
     *
     * @var array<int, list<LexerCompilerPassInterface>>
     */
    public private(set) array $passes = [];

    public function __construct()
    {
        $this->passes = [
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
    }

    /**
     * Gets (and registers, in case it does not exist yet) the group of token
     * definitions of the given lexer state.
     *
     * A state can only be reached using a {@see TokenDefinition::enter()}
     * transition and left using a {@see TokenDefinition::exit()} one.
     *
     * For example,
     * ```php
     * $builder->match('"')->enter('string');
     * $builder->state('string')->match('"')->exit();
     * ```
     *
     * @api
     *
     * @param non-empty-string $name
     */
    public function state(string $name): TokenDefinitionGroup
    {
        return $this->states[$name] ??= new TokenDefinitionGroup();
    }

    /**
     * Removes the given lexer state along with all of its token definitions.
     *
     * @api
     *
     * @param non-empty-string $name
     * @return $this
     */
    public function removeState(string $name): self
    {
        unset($this->states[$name]);

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
        $this->passes[$priority][] = $pass;

        \ksort($this->passes);

        return $this;
    }

    /**
     * @throws LexerCompilerException
     */
    public function build(): LexerBuilderResult
    {
        $context = $this->process();

        $states = [];

        foreach ($context->states as $name => $state) {
            $states[$name] = \array_values($state->tokens);
        }

        return new LexerBuilderResult(
            tokens: \array_values($context->tokens),
            states: $states,
            flags: \array_values($context->flags),
        );
    }

    /**
     * @template TArgGeneratedResult of GeneratedResult
     * @param OutputGeneratorInterface<TArgGeneratedResult> $generator
     * @return TArgGeneratedResult
     * @throws LexerCompilerException
     */
    public function generate(
        OutputGeneratorInterface $generator = new Phplrt4OutputGenerator(),
    ): GeneratedResult {
        return $generator->generate($this->build());
    }

    /**
     * @throws LexerCompilerException
     */
    private function process(): self
    {
        $context = clone $this;

        try {
            foreach ($this->passes as $passes) {
                foreach ($passes as $pass) {
                    $pass->process($context);
                }
            }
        } catch (LexerCompilerException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw LexerCompilerException::becauseInternalErrorOccurs($e);
        }

        return $context;
    }
}
