<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Config;

use Phplrt\Contracts\Lexer\LexerRuntimeExceptionInterface;
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
     * @throws LexerRuntimeExceptionInterface may throw an exception while handling the token
     */
    public function handle(ReadableInterface $source, TokenInterface $token): ?TokenInterface;
}
