<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Regex;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Exception\LexerCompilerException;

/**
 * Responsible for generating the complete PHP PCRE pattern
 */
interface RegexGeneratorInterface
{
    /**
     * @param non-empty-array<int, TokenDefinition> $tokens a map of globally
     *        unique token ID and its definition
     * @param list<RegexModifier> $flags
     * @return non-empty-string
     * @throws LexerCompilerException
     */
    public function generate(array $tokens, array $flags): string;
}
