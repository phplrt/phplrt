<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\PP2;

use Phplrt\Lexer\Builder\LexerBuilder;

/**
 * Describes the lexer reading a PP2 grammar file.
 *
 * A declaration is read as a single token whose subgroups capture its parts,
 * while a block of PHP code is read by a lexer of its own: a brace written
 * inside a string is only a brace for PHP itself.
 */
final class PP2LexerBuilder
{
    /**
     * Reads the body of a reducer written as PHP code.
     */
    private const string LEXER_PHP = 'php';

    /**
     * The declaration of a token: the state it belongs to, its name, its
     * pattern and the state it switches to.
     */
    private const string PATTERN_TOKEN_DECLARATION = <<<'REGEX'
        \h++(?:([a-zA-Z_][a-zA-Z0-9_]*+):)?([a-zA-Z_][a-zA-Z0-9_]*+)\h++(\S++)(?:\h++->\h*+(\S++))?
        REGEX;

    /**
     * A string literal along with the escaping of its own quotes.
     */
    private const string PATTERN_STRING = <<<'REGEX'
        "[^"\\]*+(?:\\.[^"\\]*+)*+"
        REGEX;

    /**
     * A name of a rule, a token or a class, optionally qualified.
     */
    private const string PATTERN_NAME = <<<'REGEX'
        \\?+[a-zA-Z_][a-zA-Z0-9_]*+(?:\\[a-zA-Z_][a-zA-Z0-9_]*+)*+
        REGEX;

    /**
     * Describes the lexer reading a PP2 grammar file.
     *
     * @api
     */
    public static function create(): LexerBuilder
    {
        $builder = new LexerBuilder();

        self::addTrivia($builder);
        self::addDeclarationTokens($builder);
        self::addStatementTokens($builder);
        self::addEmbeddedLexers($builder);

        return $builder;
    }

    /**
     * The whitespace and the comments written between the declarations.
     */
    private static function addTrivia(LexerBuilder $builder): void
    {
        $builder->addPattern('\s++')
            ->hide();
        $builder->addPattern('//[^\r\n]*+')
            ->hide();
        $builder->addPattern('/\*(.*?)\*/')
            ->hide();
    }

    /**
     * A declaration is read as a single token whose subgroups capture its
     * parts.
     *
     * Which value is which is decided by its position rather than by its
     * content: a pattern may be spelled exactly like a name and may begin with
     * the two slashes a comment begins with. A single expression knows every
     * position at once, which is what tells the values apart.
     */
    private static function addDeclarationTokens(LexerBuilder $builder): void
    {
        $builder->addPattern('%token' . self::PATTERN_TOKEN_DECLARATION, 'T_TOKEN');
        $builder->addPattern('%skip' . self::PATTERN_TOKEN_DECLARATION, 'T_SKIP');
        $builder->addPattern('%pragma\h++([a-zA-Z_][a-zA-Z0-9_.]*+)\h++(\S++)', 'T_PRAGMA');
        $builder->addPattern('%include\h++(\S++)', 'T_INCLUDE');
    }

    /**
     * Everything a rule of the parser is written of.
     */
    private static function addStatementTokens(LexerBuilder $builder): void
    {
        /**
         * A reducer written as code is told from a reducer written as a class
         * name by the brace, so the arrow alone always opens the latter.
         */
        $builder->addPattern('->\s*+(?=\{)', 'T_PHP')
            ->enter(self::LEXER_PHP);
        $builder->addValue('->', 'T_ARROW');

        // The "::=" separator is spelled with the same colons a skipped token
        // reference is surrounded by
        $builder->addPattern('::(?!=)', 'T_DOUBLE_COLON');
        $builder->addPattern('::=|:|=', 'T_EQ');

        $builder->addValue('#', 'T_HASH');
        $builder->addValue(';', 'T_SEMICOLON');
        $builder->addValue('|', 'T_OR');
        $builder->addValue('(', 'T_PARENTHESIS_OPEN');
        $builder->addValue(')', 'T_PARENTHESIS_CLOSE');
        $builder->addValue('<', 'T_ANGLE_OPEN');
        $builder->addValue('>', 'T_ANGLE_CLOSE');
        $builder->addValue('?', 'T_QUESTION_MARK');
        $builder->addValue('+', 'T_PLUS');
        $builder->addValue('*', 'T_ASTERISK');
        $builder->addValue('{', 'T_BRACE_OPEN');
        $builder->addValue('}', 'T_BRACE_CLOSE');
        $builder->addValue(',', 'T_COMMA');

        $builder->addPattern('\d++', 'T_INT');
        $builder->addPattern(self::PATTERN_STRING, 'T_STRING');
        $builder->addPattern(self::PATTERN_NAME, 'T_NAME');
    }

    private static function addEmbeddedLexers(LexerBuilder $builder): void
    {
        $builder->addEmbeddedLexer(self::LEXER_PHP, new PP2PhpEmbeddingLexer());
    }
}
