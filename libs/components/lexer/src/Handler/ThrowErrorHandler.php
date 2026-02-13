<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Handler;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Handler that throws an unknown token exception in case of any call
 */
final readonly class ThrowErrorHandler implements HandlerInterface
{
    public function handle(ReadableInterface $source, TokenInterface $token): never
    {
        throw UnrecognizedTokenException::fromToken($source, $token);
    }
}
