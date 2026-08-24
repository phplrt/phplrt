<?php

declare(strict_types=1);

namespace Phplrt\Exception\Analysis\Internal;

use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Exception\Analysis\FailureInterval;

/**
 * Locates an error of the syntax analysis by the token it has been raised on.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
final readonly class ParserFailureLocator implements FailureLocatorInterface
{
    public function tryLocate(\Throwable $e): ?FailureLocation
    {
        if (!$e instanceof RuntimeExceptionInterface) {
            return null;
        }

        // A syntax error may be as large as the whole grammar rule the
        // analysis has failed on rather than as large as one token.
        return new FailureLocation($e->source, new FailureInterval(
            offset: $e->token->offset,
            length: \max(0, $e->length ?? $e->token->size),
        ));
    }
}
