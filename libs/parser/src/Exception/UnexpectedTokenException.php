<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class UnexpectedTokenException extends UnrecognizedTokenException
{
    /**
     * @param list<non-empty-string> $expected
     */
    public static function fromToken(
        ReadableInterface $src,
        TokenInterface $tok,
        ?\Throwable $prev = null,
        array $expected = []
    ): self {
        switch (\count($expected)) {
            case 0:
                $message = \vsprintf('Syntax error, unexpected %s', [
                    self::getTokenValue($tok),
                ]);

                return new static($message, $src, $tok, $prev);

            case 1:
                $message = \vsprintf('Syntax error, unexpected %s, %s is expected', [
                    self::getTokenValue($tok),
                    self::formatTokenNames($expected),
                ]);

                return new static($message, $src, $tok, $prev);

            default:
                $message = \vsprintf('Syntax error, unexpected %s, one of %s is expected', [
                    self::getTokenValue($tok),
                    self::formatTokenNames($expected),
                ]);

                return new static($message, $src, $tok, $prev);
        }
    }

    /**
     * @param list<non-empty-string> $tokens
     *
     * @return ($tokens is non-empty-list<non-empty-string> ? non-empty-string : string)
     */
    private static function formatTokenNames(array $tokens): string
    {
        return \implode(', ', \array_map(
            static fn(string $t): string => \sprintf('"%s"', $t),
            $tokens,
        ));
    }
}
