<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Config;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Handler that returns nothing.
 */
final class NullHandler implements HandlerInterface
{
    public function handle(ReadableInterface $source, TokenInterface $token): ?TokenInterface
    {
        return null;
    }
}
