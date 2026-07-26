<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Regex;

use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Exception\LexerCompilerException;

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
