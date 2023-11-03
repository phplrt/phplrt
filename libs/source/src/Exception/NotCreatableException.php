<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Error that occurs when a {@see ReadableInterface} object cannot be created.
 */
class NotCreatableException extends NotAccessibleException
{
    /**
     * @final
     */
    public const CODE_INVALID_TYPE = 0x01 + parent::CODE_LAST;

    protected const CODE_LAST = self::CODE_INVALID_TYPE;

    public static function fromInvalidType($source): self
    {
        $message = \vsprintf('Cannot create %s instance from %s', [
            ReadableInterface::class,
            \get_debug_type($source)
        ]);

        return new static($message, self::CODE_INVALID_TYPE);
    }
}
