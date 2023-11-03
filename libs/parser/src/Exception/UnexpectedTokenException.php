<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class UnexpectedTokenException extends UnrecognizedTokenException
{
    public const ERROR_UNRECOGNIZED_TOKEN = 'Syntax error, unexpected %s';

    /**
     * @param list<non-empty-string> $expected
     */
    public static function fromToken(
        ReadableInterface $src,
        TokenInterface $tok,
        \Throwable $prev = null,
        array $expected = []
    ): self {
        $tokens = \implode(', ', \array_map(
            static fn (string $t): string => \sprintf('"%s"', $t),
            $expected
        ));

        switch (\count($expected)) {
            case 0:
                $message = \vsprintf(self::ERROR_UNRECOGNIZED_TOKEN, [
                    self::getTokenValue($tok),
                ]);

                return new static($message, $src, $tok, $prev);
            case 1:
                $message = \vsprintf('Syntax error, unexpected %s, %s is expected', [
                    self::getTokenValue($tok),
                    $tokens,
                ]);

                return new static($message, $src, $tok, $prev);
            default:
                $message = \vsprintf('Syntax error, unexpected %s, one of %s is expected', [
                    self::getTokenValue($tok),
                    $tokens,
                ]);

                return new static($message, $src, $tok, $prev);
        }
    }
}
