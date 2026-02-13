<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Handler;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Handler that returns nothing which means the token will be skipped
 */
final readonly class SkipTokenHandler implements HandlerInterface
{
    public function handle(ReadableInterface $source, TokenInterface $token): null
    {
        return null;
    }
}
