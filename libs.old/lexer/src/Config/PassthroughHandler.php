<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Config;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Handler that returns the token "as is".
 */
final class PassthroughHandler implements HandlerInterface
{
    public function handle(ReadableInterface $source, TokenInterface $token): ?TokenInterface
    {
        return $token;
    }
}
