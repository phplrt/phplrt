<?php

declare(strict_types=1);

namespace Phplrt\Example\Php;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\EndOfInputToken;

/**
 * Reads the block of PHP code a reducer is written of, from the brace opening
 * it up to the brace closing it.
 *
 * The braces are counted the way PHP reads them, so a brace written inside a
 * string or a comment is not mistaken for the end of the block.
 */
final readonly class PhpBlockLexer implements LexerInterface
{
    private const string BRACE_OPEN = '{';

    private const string BRACE_CLOSE = '}';

    /**
     * The tokens PHP opens a brace with that are not a brace of their own: an
     * interpolated expression ("{$name}") and an interpolated variable
     * ("${name}").
     *
     * @var list<int>
     */
    private const array INTERPOLATION_TOKENS = [\T_CURLY_OPEN, \T_DOLLAR_OPEN_CURLY_BRACES];

    public function __construct(
        private LexerInterface $lexer = new PhpLexer(),
    ) {}

    public function lex(ReadableInterface $source, int $offset = 0): iterable
    {
        $result = [];
        $end = $offset;
        $depth = 0;

        foreach ($this->lexer->lex($source, $offset) as $token) {
            if ($token->channel === Channel::EndOfInput) {
                break;
            }

            $result[] = $token;
            $end = $token->offset + $token->size;

            if ($token->value === self::BRACE_OPEN
                || \in_array($token->id, self::INTERPOLATION_TOKENS, true)
            ) {
                ++$depth;

                continue;
            }

            if ($token->value !== self::BRACE_CLOSE) {
                continue;
            }

            // The block ends where the brace that opened it is closed
            if (--$depth === 0) {
                break;
            }
        }

        $result[] = new EndOfInputToken($end);

        return $result;
    }
}
