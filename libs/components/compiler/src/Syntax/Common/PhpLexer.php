<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\Common;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;

/**
 * Reads PHP code the way PHP itself reads it.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Compiler
 */
final readonly class PhpLexer implements LexerInterface
{
    /**
     * The opening tag the code is read with, so that it is read as code
     * rather than as inline HTML.
     */
    private const string OPENING_TAG = '<?php ';

    public function lex(ReadableInterface $source, int $offset = 0): iterable
    {
        $tag = \strlen(self::OPENING_TAG);
        $code = self::OPENING_TAG . \substr($source->content, $offset);

        $result = [];
        $end = $offset;

        foreach (\PhpToken::tokenize($code) as $index => $token) {
            // The opening tag belongs to the reading rather than to the source
            if ($index === 0) {
                continue;
            }

            // An empty token describes no fragment of the source
            if ($token->text === '') {
                continue;
            }

            /** @var int<0, max> $at */
            $at = $offset + $token->pos - $tag;

            $result[] = new Token(
                id: $token->id,
                name: $token->getTokenName(),
                channel: Channel::Default,
                value: $token->text,
                offset: $at,
            );

            $end = $at + \strlen($token->text);
        }

        $result[] = new EndOfInputToken($end);

        return $result;
    }
}
