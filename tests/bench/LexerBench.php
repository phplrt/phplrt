<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Bench;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\LexerCreateInfo;
use Phplrt\Source\File;

#[BeforeMethods('boot')]
#[Revs(3), Iterations(5)]
class LexerBench
{
    private const TOKEN_DEFINITIONS = [
        'T_COMMENT'            => '(//|#)[^\\n]*\n',
        'T_DOC_COMMENT'        => '/\\*.*?\\*/',
        'T_WHITESPACE'         => '(\\xfe\\xff|\\x20|\\x09|\\x0a|\\x0d)+',
        'T_HEREDOC'            => '<<<\h*(\w+)[\s\S]*?\n\h*\g{-1}',
        'T_NOWDOC'             => '<<<\h*\'(\w+)\'[\s\S]*?\n\h*\g{-1}',
        'T_INTERPOLATE_STRING' => '"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"',
        'T_STRING'             => '\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'',
        'T_CAST'               => '\((real|double|float|bool|boolean|array|string|unset|object|int)\)',
        'T_HEX_NUMBER'         => '0x[0-9a-fA-F]+(?:[eE][\\+\\-]?[0-9]+)?',
        'T_OCT_NUMBER'         => '0[0-9]+(?:[eE][\\+\\-]?[0-9]+)?',
        'T_FLOAT_NUMBER'       => '([0-9]*\\.[0-9]+|[0-9]+\\.[0-9]*)(?:[eE][\\+\\-]?[0-9]+)?',
        'T_INT_NUMBER'         => '(?:0|[1-9][0-9]*)(?:[eE][\\+\\-]?[0-9]+)?',
        'T_VARIABLE'           => '\$[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*\b',
        'T_NS'                 => '\\\\',
        'T_OBJECT_OPERATOR'    => '\->',
        'T_DOUBLE_ARROW'       => '=>',
        'T_OPEN_TAG_WITH_ECHO' => '<(\?|%)=',
        'T_OPEN_TAG'           => '(<\?php|<\?|<%)\s?',
        'T_CLOSE_TAG'          => '\?>|%>',
        'T_INCREMENT'          => '\+\+',
        'T_DECREMENT'          => '\-\-',
        'T_COALESCE'           => '\?\?',
        'T_POW'                => '\*\*',
        'T_AT'                 => '@',
        'T_PLUS_EQUAL'         => '\+=',
        'T_MINUS_EQUAL'        => '\-=',
        'T_MUL_EQUAL'          => '\*=',
        'T_DIV_EQUAL'          => '/=',
        'T_CONCAT_EQUAL'       => '\.=',
        'T_MOD_EQUAL'          => '%=',
        'T_AND_EQUAL'          => '&=',
        'T_OR_EQUAL'           => '\|=',
        'T_XOR_EQUAL'          => '\^=',
        'T_SL_EQUAL'           => '<<=',
        'T_SR_EQUAL'           => '>>=',
        'T_IDENTICAL'          => '===',
        'T_NOT_IDENTICAL'      => '!==',
        'T_EQUAL'              => '==',
        'T_NOT_EQUAL'          => '!=',
        'T_NOT'                => '!',
        'T_ASSIGN'             => '=',
        'T_BOOLEAN_AND'        => '&&',
        'T_BOOLEAN_OR'         => '\|\|',
        'T_BIN_AND'            => '&',
        'T_BIN_OR'             => '\|',
        'T_BIN_NOT'            => '~',
        'T_ELLIPSIS'           => '\.\.\.',
        'T_MINUS'              => '\-',
        'T_PLUS'               => '\+',
        'T_MUL'                => '\*',
        'T_CONCAT'             => '\.',
        'T_DIV'                => '/',
        'T_MOD'                => '%',
        'T_XOR'                => '\^',
        'T_SPACESHIP'          => '<=>',
        'T_SL'                 => '<<',
        'T_SR'                 => '>>',
        'T_GREATER_OR_EQUAL'   => '>=',
        'T_GREATER'            => '>',
        'T_LESSOR_EQUAL'       => '<=',
        'T_LESS'               => '<',
        'T_SEMICOLON'          => ';',
        'T_DOUBLE_COLON'       => '::',
        'T_COLON'              => ':',
        'T_Q_MARK'             => '\?',
        'T_COMMA'              => ',',
        'T_LOGICAL_OR'         => 'or\b',
        'T_LOGICAL_AND'        => 'and\b',
        'T_LOGICAL_XOR'        => 'xor\b',
        'T_PARENTHESIS_OPEN'   => '\\(',
        'T_PARENTHESIS_CLOSE'  => '\\)',
        'T_BRACKET_OPEN'       => '\\[',
        'T_BRACKET_CLOSE'      => '\\]',
        'T_BRACE_OPEN'         => '{',
        'T_BRACE_CLOSE'        => '}',
        'T_NEW'                => 'new\b',
        'T_CLONE'              => 'clone\b',
        'T_EXIT'               => 'exit\b',
        'T_IF'                 => 'if\b',
        'T_ELSEIF'             => 'elseif\b',
        'T_ELSE'               => 'else\b',
        'T_ENDIF'              => 'endif\b',
        'T_ECHO'               => 'echo\b',
        'T_DO'                 => 'do\b',
        'T_WHILE'              => 'while\b',
        'T_ENDWHILE'           => 'endwhile\b',
        'T_FOR'                => 'for\b',
        'T_ENDFOR'             => 'endfor\b',
        'T_FOREACH'            => 'foreach\b',
        'T_ENDFOREACH'         => 'endforeach\b',
        'T_DECLARE'            => 'declare\b',
        'T_ENDDECLARE'         => 'enddeclare\b',
        'T_AS'                 => 'as\b',
        'T_SWITCH'             => 'switch\b',
        'T_ENDSWITCH'          => 'endswitch\b',
        'T_CASE'               => 'case\b',
        'T_DEFAULT'            => 'default\b',
        'T_BREAK'              => 'break\b',
        'T_CONTINUE'           => 'continue\b',
        'T_GOTO'               => 'goto\b',
        'T_FUNCTION'           => 'function\b',
        'T_CONST'              => 'const\b',
        'T_RETURN'             => 'return\b',
        'T_TRY'                => 'try\b',
        'T_CATCH'              => 'catch\b',
        'T_FINALLY'            => 'finally\b',
        'T_THROW'              => 'throw\b',
        'T_USE'                => 'use\b',
        'T_INSTEADOF'          => 'insteadof\b',
        'T_GLOBAL'             => 'global\b',
        'T_STATIC'             => 'static\b',
        'T_ABSTRACT'           => 'abstract\b',
        'T_FINAL'              => 'final\b',
        'T_PRIVATE'            => 'private\b',
        'T_PROTECTED'          => 'protected\b',
        'T_PUBLIC'             => 'public\b',
        'T_VAR'                => 'var\b',
        'T_UNSET'              => 'unset\b',
        'T_ISSET'              => 'isset\b',
        'T_EMPTY'              => 'empty\b',
        'T_HALT_COMPILER'      => '__halt_compiler\b',
        'T_CLASS'              => 'class\b',
        'T_TRAIT'              => 'trait\b',
        'T_INTERFACE'          => 'interface\b',
        'T_EXTENDS'            => 'extends\b',
        'T_IMPLEMENTS'         => 'implements\b',
        'T_LIST'               => 'list\b',
        'T_ARRAY'              => 'array\b',
        'T_CALLABLE'           => 'callable\b',
        'T_LINE'               => '__LINE__\b',
        'T_FILE'               => '__FILE__\b',
        'T_DIR'                => '__DIR__\b',
        'T_CLASS_C'            => '__CLASS__\b',
        'T_TRAIT_C'            => '__TRAIT__\b',
        'T_METHOD_C'           => '__METHOD__\b',
        'T_FUNC_C'             => '__FUNCTION__\b',
        'T_NS_C'               => '__NAMESPACE__\b',
        'T_TRUE'               => 'true\b',
        'T_FALSE'              => 'false\b',
        'T_NULL'               => 'null\b',
        'T_NAMESPACE'          => 'namespace\b',
        'T_SELF'               => 'self\b',
        'T_INSTANCEOF  '       => 'instanceof\b',
        'T_PARENT'             => 'parent\b',
        'T_YIELD_FROM'         => 'yield\s+from\b',
        'T_YIELD'              => 'yield\b',
        'T_DIE'                => 'die\b',
        'T_PRINT'              => 'print\b',
        'T_EVAL'               => 'eval\b',
        'T_INCLUDE'            => 'include\b',
        'T_INCLUDE_ONCE'       => 'include_once\b',
        'T_REQUIRE'            => 'require\b',
        'T_REQUIRE_ONCE'       => 'require_once\b',
        'T_CONST_STRING'       => '[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*',
    ];

    /**
     * @var iterable<\SplFileInfo>
     */
    private iterable $files;

    /**
     * @var LexerInterface
     */
    private LexerInterface $lexer;

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->files = new \RegexIterator(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(__DIR__ . '/../../libs')
        ), '/\.php$/i');

        $this->lexer = new Lexer(new LexerCreateInfo(
            tokens: self::TOKEN_DEFINITIONS,
            skip: ['T_COMMENT', 'T_DOC_COMMENT', 'T_WHITESPACE']
        ));
    }

    #[Warmup(2)]
    public function benchPhplrt4(): void
    {
        foreach ($this->files as $file) {
            foreach ($this->lexer->lex($file) as $token) {
                // $token
            }
        }
    }

    #[Warmup(2)]
    public function benchNative(): void
    {
        foreach ($this->files as $file) {
            token_get_all(File::new($file)->getContents());
        }
    }
}
