<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Regex;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Exception\LexerCompilerException;

/**
 * Responsible for generating the complete PHP PCRE pattern
 *
 * @template-covariant TResult of RegexGeneratorResult = RegexGeneratorResult
 */
interface RegexGeneratorInterface
{
    /**
     * @param array<int, TokenDefinition> $tokens a map of globally unique
     *        token ID and its definition
     * @param list<RegexModifier> $flags
     * @throws LexerCompilerException
     */
    public function generate(array $tokens, array $flags): RegexGeneratorResult;
}
