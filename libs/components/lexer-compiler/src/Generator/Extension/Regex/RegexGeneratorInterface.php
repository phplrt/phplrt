<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Generator\Extension\Regex;

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
     * @param list<TokenDefinition> $tokens
     * @param list<RegexModifier> $flags
     *
     * @return RegexGeneratorResult
     * @throws LexerCompilerException
     */
    public function generate(array $tokens, array $flags): RegexGeneratorResult;
}
