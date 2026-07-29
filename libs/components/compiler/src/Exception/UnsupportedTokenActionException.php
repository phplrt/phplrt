<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Node\Declaration\TokenAction;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a token is told to do something the compiler knows nothing
 * about, or is told to do it in a way it cannot be done.
 */
final class UnsupportedTokenActionException extends UnsupportedSyntaxException
{
    public static function becauseActionIsNotSupported(
        ReadableInterface $source,
        TokenAction $action,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('Unrecognized token action "%s"', $action->name);

        return self::create($source, $action, $message, $previous);
    }

    public static function becauseActionExpectsValue(
        ReadableInterface $source,
        TokenAction $action,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('The "%s" action of a token expects a value', $action->name);

        return self::create($source, $action, $message, $previous);
    }

    public static function becauseActionExpectsNoValue(
        ReadableInterface $source,
        TokenAction $action,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('The "%s" action of a token expects no value', $action->name);

        return self::create($source, $action, $message, $previous);
    }

    public static function becauseReadingIsMovedTwice(
        ReadableInterface $source,
        TokenAction $action,
        TokenAction $conflict,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf(
            'A token is read once, so the "%s" action cannot be applied after the "%s" one',
            $action->name,
            $conflict->name,
        );

        return self::create($source, $action, $message, $previous);
    }

    private static function create(
        ReadableInterface $source,
        TokenAction $action,
        string $message,
        ?\Throwable $previous,
    ): self {
        return new self(
            source: $source,
            offset: $action->offset,
            message: $message,
            end: $action->offset + $action->length,
            previous: $previous,
        );
    }
}
