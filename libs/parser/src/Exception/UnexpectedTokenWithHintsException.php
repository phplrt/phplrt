<?php

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class UnexpectedTokenWithHintsException extends UnexpectedTokenException
{
    /**
     * @param ReadableInterface $src
     * @param TokenInterface $tok
     * @param \Throwable|null $prev
     * @param array $possibleTokens
     * @return static
     */
    public static function fromToken(
        ReadableInterface $src,
        TokenInterface $tok,
        \Throwable $prev = null,
        array $possibleTokens = []
    ): self {
        $errorMessage = (TokenInterface::END_OF_INPUT === $tok->getName())
            ? 'Unexpected end of code'
            : self::ERROR_UNRECOGNIZED_TOKEN;
        $possibleTokensString = '';
        if ($possibleTokens !== []) {
            $possibleTokensString = $possibleTokens !== []
                ? 'Expected ' . \implode(', ', $possibleTokens) . '. '
                : ''
            ;
        }

        $message = \sprintf(
            $errorMessage . '. '
            . $possibleTokensString,
            '"' . $tok->getValue() . '" (' . $tok->getName() . ')'
        );

        return new static($message, $src, $tok, $prev);
    }
}
