<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Compiler;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Definition\TerminalRuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenIdRuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenNameRuleDefinition;
use Phplrt\Compiler\Parser\Definition\TokenRuleDefinition;
use Phplrt\Compiler\Parser\Exception\CompilationFailedException;
use Phplrt\Compiler\Parser\ParserBuilder;

/**
 * Checks that every token referred to by the grammar is recognized by the lexer
 * and reaches the parser.
 */
final readonly class TokenReferenceValidationParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void
    {
        foreach ($builder->rules as $rule) {
            if (!$rule instanceof TerminalRuleDefinition) {
                continue;
            }

            $token = $this->findToken($lexer, $rule);

            if ($token === null) {
                throw CompilationFailedException::becauseTokenIsUnknown($rule);
            }

            /**
             * The hidden tokens are excluded from the token stream by the
             * default filter of the parser, so such a rule would never be
             * recognized.
             */
            if ($token->isHidden) {
                throw new CompilationFailedException($rule, \sprintf(
                    'Rule %s refers to the hidden token %s, which does not reach the parser',
                    $rule,
                    $token,
                ));
            }
        }
    }

    private function findToken(LexerBuilderResult $lexer, TerminalRuleDefinition $rule): ?TokenDefinition
    {
        return match (true) {
            $rule instanceof TokenIdRuleDefinition => $this->findTokenById($lexer, $rule->tokenId),
            $rule instanceof TokenNameRuleDefinition => $this->findTokenByName($lexer, $rule->tokenName),
            $rule instanceof TokenRuleDefinition => $this->findTokenByDefinition($lexer, $rule->token),
        };
    }

    /**
     * A definition belonging to another lexer is not recognized by this one.
     */
    private function findTokenByDefinition(LexerBuilderResult $lexer, TokenDefinition $token): ?TokenDefinition
    {
        if ($lexer->findTokenId($token) === null) {
            return null;
        }

        return $token;
    }

    private function findTokenById(LexerBuilderResult $lexer, int $id): ?TokenDefinition
    {
        foreach ([$lexer->tokens, ...\array_values($lexer->states)] as $definitions) {
            if (isset($definitions[$id])) {
                return $definitions[$id];
            }
        }

        return null;
    }

    /**
     * @param non-empty-string $name
     */
    private function findTokenByName(LexerBuilderResult $lexer, string $name): ?TokenDefinition
    {
        $id = $lexer->constants[$name] ?? null;

        if ($id === null) {
            return null;
        }

        return $this->findTokenById($lexer, $id);
    }
}
