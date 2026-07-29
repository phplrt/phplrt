<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Stub;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;

/**
 * Reads the arithmetic expressions, like "1 + 2 - 3".
 */
final readonly class ArithmeticLexer implements LexerInterface
{
    public const int T_NUMBER = 0;

    public const int T_PLUS = 1;

    public const int T_MINUS = 2;

    /**
     * @var non-empty-string
     */
    private const string PATTERN = '/\G(?:(?<T_NUMBER>\d++)|(?<T_PLUS>\+)|(?<T_MINUS>-)|(?<skip>\s++))/Ssu';

    /**
     * @var array<non-empty-string, int<0, max>>
     */
    private const array IDENTIFIERS = [
        'T_NUMBER' => self::T_NUMBER,
        'T_PLUS' => self::T_PLUS,
        'T_MINUS' => self::T_MINUS,
    ];

    public function lex(ReadableInterface $source, int $offset = 0): iterable
    {
        $content = $source->content;

        \preg_match_all(self::PATTERN, $content, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE, $offset);

        foreach ($matches as $match) {
            foreach (self::IDENTIFIERS as $name => $id) {
                if (($match[$name][0] ?? '') !== '') {
                    yield new Token($id, $name, Channel::Default, $match[$name][0], \max(0, $match[$name][1]));
                }
            }
        }

        yield new EndOfInputToken(\strlen($content));
    }

    public static function describe(TokenInterface $token): string
    {
        return \sprintf('%s(%s)', $token->name ?? '?', $token->value);
    }
}
