<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\Exception\SourceCreationExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Error that occurs when a {@see ReadableInterface} object cannot be created
 */
class NotCreatableException extends NotAccessibleException implements
    SourceCreationExceptionInterface
{
    final public const int CODE_INVALID_TYPE = 0x01;
    final public const int CODE_UNSUPPORTED_TYPE = 0x02;

    /**
     * @param non-empty-string $expected
     */
    public static function becauseSourceIs(mixed $source, string $expected, ?\Throwable $prev = null): self
    {
        /** @phpstan-ignore-next-line : False-positive, get_debug_type returns non-empty string */
        return self::becauseSourceIsTypeOf(\get_debug_type($source), $expected, $prev);
    }

    /**
     * @param non-empty-string $actual
     * @param non-empty-string $expected
     */
    public static function becauseSourceIsTypeOf(string $actual, string $expected, ?\Throwable $prev = null): self
    {
        $message = \sprintf('Cannot create source instance from %s, expected %s', $actual, $expected);

        return new self($message, self::CODE_INVALID_TYPE, $prev);
    }

    public static function becauseSourceIsUnsupported(mixed $source, ?\Throwable $prev = null): self
    {
        $message = \sprintf('Cannot create source instance from %s, no suitable driver found', \get_debug_type($source));

        return new self($message, self::CODE_UNSUPPORTED_TYPE, $prev);
    }
}
