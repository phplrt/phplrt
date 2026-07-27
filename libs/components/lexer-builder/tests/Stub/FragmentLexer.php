<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests\Stub;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;

/**
 * Reads the source up to the closing bracket and stops there, the way a lexer
 * embedded into a grammar is expected to behave.
 */
final readonly class FragmentLexer implements LexerInterface
{
    public const int T_FRAGMENT = 100;

    public function lex(string $source, int $offset = 0): iterable
    {
        $end = \strpos($source, ']', $offset);
        $end = $end === false ? \strlen($source) : $end;

        $result = [];

        if ($end > $offset) {
            $result[] = new Token(
                id: self::T_FRAGMENT,
                name: 'T_FRAGMENT',
                channel: Channel::Default,
                value: \substr($source, $offset, $end - $offset),
                offset: $offset,
            );
        }

        $result[] = new EndOfInputToken($end);

        return $result;
    }
}
