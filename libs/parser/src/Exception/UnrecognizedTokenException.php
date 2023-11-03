<?php

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface as ExceptionContract;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\Renderer;

class UnrecognizedTokenException extends ParserRuntimeException
{
    /**
     * @var string
     */
    public const ERROR_UNRECOGNIZED_TOKEN = 'Syntax error, unrecognized %s';

    /**
     * @var array{list<non-empty-string>, list<non-empty-string>}
     */
    private const DEFAULT_REPLACEMENTS = [
        ["\0", "\n", "\t"],
        ['\0', '\n', '\t'],
    ];

    public static function fromToken(ReadableInterface $src, TokenInterface $tok, \Throwable $prev = null): self
    {
        $message = \sprintf(self::ERROR_UNRECOGNIZED_TOKEN, self::getTokenValue($tok));

        return new static($message, $src, $tok, $prev);
    }

    public static function fromLexerException(ExceptionContract $e): self
    {
        $token = $e->getToken();
        $source = $e->getSource();

        return static::fromToken($source, $token, $e);
    }

    protected static function getTokenValue(TokenInterface $token): string
    {
        if (\class_exists(Renderer::class)) {
            return (new Renderer())->render($token);
        }

        $replacements = self::DEFAULT_REPLACEMENTS;

        $value = \str_replace($replacements[0], $replacements[1], $token->getValue());

        return \sprintf('"%s" (%s)', $value, $token->getName());
    }
}
