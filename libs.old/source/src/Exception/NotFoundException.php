<?php

declare(strict_types=1);

namespace Phplrt\Source\Exception;

/**
 * The exception that occurs in the absence of a file in the file system.
 */
class NotFoundException extends NotReadableException
{
    /**
     * @final
     */
    public const CODE_NOT_FOUND = 0x01 + parent::CODE_LAST;

    protected const CODE_LAST = self::CODE_NOT_FOUND + parent::CODE_LAST;

    /**
     * @psalm-taint-sink file $pathname
     * @param non-empty-string $pathname
     *
     * @return static
     */
    public static function fromInvalidPathname(string $pathname): self
    {
        $message = 'File "%s" not found';

        return new static(\sprintf($message, $pathname), self::CODE_NOT_FOUND);
    }
}
