<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\TerminalRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenIdRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenNameRuleDefinition;
use Phplrt\Parser\Builder\Definition\TokenRuleDefinition;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;

/**
 * Checks that every token referred to by the grammar is recognized by the lexer
 * and reaches the parser.
 */
final readonly class TokenReferenceValidationParserCompilerPass implements
    ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        foreach ($context->rules as $rule) {
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
        $id = \array_search($name, $lexer->names, true);

        if ($id === false) {
            return null;
        }

        return $this->findTokenById($lexer, $id);
    }
}
