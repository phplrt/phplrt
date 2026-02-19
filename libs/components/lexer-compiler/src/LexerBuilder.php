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
use Phplrt\Compiler\Lexer\Exception\LexerCompilerException;
use Phplrt\Compiler\Lexer\Generator\GeneratedResult;
use Phplrt\Compiler\Lexer\Generator\OutputGeneratorInterface;
use Phplrt\Compiler\Lexer\Generator\Phplrt4OutputGenerator;

final class LexerBuilder
{
    use HasTokenDefinitions;
    use HasRegexFlags;

    /**
     * @var array<non-empty-string, TokenDefinitionGroup>
     */
    public private(set) array $states = [];

    /**
     * @var array<array-key, list<LexerCompilerPassInterface>>
     */
    public private(set) array $passes = [];

    public function __construct()
    {
        $this->passes = [
            0 => [
                new TokenNameDuplicationLexerCompilerPass(),
                new TokenNameValidationLexerCompilerPass(),
                new RegexDuplicationLexerCompilerPass(),
                new RegexValidationLexerCompilerPass(),
                new RegexExcessiveGreedLexerCompilerPass(),
            ],
        ];
    }

    /**
     * @return $this
     */
    public function addCompilerPass(LexerCompilerPassInterface $pass, int $priority = 0): self
    {
        $this->passes[$priority][] = $pass;

        return $this;
    }

    /**
     * @template TArgGeneratedResult of GeneratedResult
     *
     * @param OutputGeneratorInterface<TArgGeneratedResult> $generator
     *
     * @return TArgGeneratedResult
     * @throws LexerCompilerException
     */
    public function build(
        OutputGeneratorInterface $generator = new Phplrt4OutputGenerator(),
    ): GeneratedResult {
        $context = $this->process();

        $states = [];

        foreach ($this->states as $name => $state) {
            $states[$name] = \array_values($state->tokens);
        }

        return $generator->generate(new LexerBuilderResult(
            tokens: \array_values($context->tokens),
            states: $states,
            flags: \array_values($context->flags),
        ));
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
