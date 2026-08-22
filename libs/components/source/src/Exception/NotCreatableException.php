<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Error that occurs when a {@see ReadableInterface} object cannot be created
 */
class NotCreatableException extends NotAccessibleException
{
    /**
     * @param non-empty-string $type
     */
    public static function becauseSourceIs(string $type, ?\Throwable $prev = null): self
    {
        $message = \vsprintf('Cannot create %s instance from %s type', [
            ReadableInterface::class,
            $type,
        ]);

        return new self($message, previous: $prev);
    }

    public static function becauseSourceIsInvalid(mixed $source, ?\Throwable $prev = null): self
    {
        /** @phpstan-ignore-next-line : False-positive, get_debug_type returns non-empty string */
        return self::becauseSourceIs(\get_debug_type($source), $prev);
    }
}
