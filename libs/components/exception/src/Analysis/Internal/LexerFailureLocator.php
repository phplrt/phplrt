<?php

declare(strict_types=1);

namespace Phplrt\Exception\Analysis\Internal;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Exception\Analysis\FailureInterval;

/**
 * Locates an error of the lexical analysis by the token it has been raised on.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
final readonly class LexerFailureLocator implements FailureLocatorInterface
{
    public function tryLocate(\Throwable $e): ?FailureLocation
    {
        if (!$e instanceof RuntimeExceptionInterface) {
            return null;
        }

        return new FailureLocation($e->source, new FailureInterval(
            offset: $e->token->offset,
            length: $e->token->size,
        ));
    }
}
