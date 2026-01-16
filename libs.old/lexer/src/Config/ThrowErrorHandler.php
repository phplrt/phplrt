<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Config;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;

/**
 * Handler that throws an unknown token exception in case of any call.
 */
final class ThrowErrorHandler implements HandlerInterface
{
    public function handle(ReadableInterface $source, TokenInterface $token): ?TokenInterface
    {
        throw UnrecognizedTokenException::fromToken($source, $token);
    }
}
