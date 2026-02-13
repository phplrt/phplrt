<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Handler;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * A handler called while processing a token.
 */
interface HandlerInterface
{
    /**
     * The method itself, which is executed while processing a token.
     *
     * @throws RuntimeExceptionInterface may throw an exception while handling the token
     */
    public function handle(ReadableInterface $source, TokenInterface $token): ?TokenInterface;
}
