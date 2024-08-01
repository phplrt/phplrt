<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Grammar;

use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\PositionalLexerInterface;

final class PP2Lexer implements PositionalLexerInterface
{
    /**
     * @var non-empty-string
     */
    public const T_PRAGMA = 'T_PRAGMA';

    /**
     * @var non-empty-string
     */
    public const T_INCLUDE = 'T_INCLUDE';

    /**
     * @var non-empty-string
     */
    public const T_TOKEN_DEF = 'T_TOKEN_DEF';

    /**
     * @var non-empty-string
     */
    public const T_SKIP_DEF = 'T_SKIP_DEF';

    /**
     * @var non-empty-string
     */
    public const T_OR = 'T_OR';

    /**
     * @var non-empty-string
     */
    public const T_TOKEN_SKIPPED = 'T_TOKEN_SKIPPED';

    /**
     * @var non-empty-string
     */
    public const T_TOKEN_KEPT = 'T_TOKEN_KEPT';

    /**
     * @var non-empty-string
     */
    public const T_TOKEN_STRING = 'T_TOKEN_STRING';

    /**
     * @var non-empty-string
     */
    public const T_INVOKE = 'T_INVOKE';

    /**
     * @var non-empty-string
     */
    public const T_GROUP_OPEN = 'T_GROUP_OPEN';

    /**
     * @var non-empty-string
     */
    public const T_GROUP_CLOSE = 'T_GROUP_CLOSE';

    /**
     * @var non-empty-string
     */
    public const T_REPEAT_ZERO_OR_ONE = 'T_REPEAT_ZERO_OR_ONE';

    /**
     * @var non-empty-string
     */
    public const T_REPEAT_ONE_OR_MORE = 'T_REPEAT_ONE_OR_MORE';

    /**
     * @var non-empty-string
     */
    public const T_REPEAT_ZERO_OR_MORE = 'T_REPEAT_ZERO_OR_MORE';

    /**
     * @var non-empty-string
     */
    public const T_REPEAT_N_TO_M = 'T_REPEAT_N_TO_M';

    /**
     * @var non-empty-string
     */
    public const T_REPEAT_N_OR_MORE = 'T_REPEAT_N_OR_MORE';

    /**
     * @var non-empty-string
     */
    public const T_REPEAT_ZERO_TO_M = 'T_REPEAT_ZERO_TO_M';

    /**
     * @var non-empty-string
     */
    public const T_REPEAT_EXACTLY_N = 'T_REPEAT_EXACTLY_N';

    /**
     * @var non-empty-string
     */
    public const T_KEPT_NAME = 'T_KEPT_NAME';

    /**
     * @var non-empty-string
     */
    public const T_NAME = 'T_NAME';

    /**
     * @var non-empty-string
     */
    public const T_EQ = 'T_EQ';

    /**
     * @var non-empty-string
     */
    public const T_END_OF_RULE = 'T_END_OF_RULE';

    /**
     * @var non-empty-string
     */
    public const T_WHITESPACE = 'T_WHITESPACE';

    /**
     * @var non-empty-string
     */
    public const T_COMMENT = 'T_COMMENT';

    /**
     * @var non-empty-string
     */
    public const T_BLOCK_COMMENT = 'T_BLOCK_COMMENT';

    /**
     * @var non-empty-string
     */
    public const T_ARROW_RIGHT = 'T_ARROW_RIGHT';

    /**
     * @var non-empty-string
     */
    public const T_PHP_OPEN = 'T_PHP_OPEN';

    /**
     * @var non-empty-string
     */
    public const T_PHP_CLOSE = 'T_PHP_CLOSE';

    /**
     * Lexical tokens list.
     *
     * @var non-empty-array<non-empty-string, non-empty-string>
     */
    private const LEXER_TOKENS = [
        self::T_PRAGMA => '%pragma\\h+([\\w\\.]+)\\h+([^\\s]+)',
        self::T_INCLUDE => '%include\\h+([^\\s]+)',
        self::T_TOKEN_DEF => '%token\\h+(?:(\\w+):)?(\\w+)\\h+([^\\s]+)(?:\\h*\\->\\h*([^\\s]+))?',
        self::T_SKIP_DEF => '%skip\\h+(\\w+)\\h+([^\\s]+)',
        self::T_OR => '\\|',
        self::T_TOKEN_SKIPPED => '::(\\w+)::',
        self::T_TOKEN_KEPT => '<(\\w+)>',
        self::T_TOKEN_STRING => '"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"',
        self::T_INVOKE => '([a-zA-Z0-9_]+)\\(\\)',
        self::T_GROUP_OPEN => '\\(',
        self::T_GROUP_CLOSE => '\\)',
        self::T_REPEAT_ZERO_OR_ONE => '\\?',
        self::T_REPEAT_ONE_OR_MORE => '\\+',
        self::T_REPEAT_ZERO_OR_MORE => '\\*',
        self::T_REPEAT_N_TO_M => '{\\h*(\\-?\\d+)\\h*,\\h*(\\-?\\d+)\\h*}',
        self::T_REPEAT_N_OR_MORE => '{\\h*(\\-?\\d+)\\h*,\\h*}',
        self::T_REPEAT_ZERO_TO_M => '{\\h*,\\h*(\\-?\\d+)\\h*}',
        self::T_REPEAT_EXACTLY_N => '{\\h*(\\-?\\d+)\\h*}',
        self::T_KEPT_NAME => '#',
        self::T_NAME => '\\\\?[a-zA-Z0-9_]+(?:\\\\[a-zA-Z0-9_]+)*',
        self::T_EQ => '(?:\\:\\:=|\\:|=)',
        self::T_END_OF_RULE => ';',
        self::T_PHP_OPEN => '\->\h*{',
        self::T_ARROW_RIGHT => '\->',
        self::T_PHP_CLOSE => '}',
        self::T_WHITESPACE => '(\\xfe\\xff|\\x20|\\x09|\\x0a|\\x0d)+',
        self::T_COMMENT => '//[^\\n]*',
        self::T_BLOCK_COMMENT => '/\\*.*?\\*/',
    ];

    /**
     * List of skipped tokens.
     *
     * @var non-empty-array<non-empty-string>
     */
    private const LEXER_SKIPPED_TOKENS = [
        'T_WHITESPACE',
        'T_COMMENT',
        'T_BLOCK_COMMENT',
    ];

    private Lexer $lexer;

    public function __construct()
    {
        $this->lexer = new Lexer(self::LEXER_TOKENS, self::LEXER_SKIPPED_TOKENS);
    }

    public function lex(mixed $source, int $offset = 0): iterable
    {
        return $this->lexer->lex($source, $offset);
    }
}
