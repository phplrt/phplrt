<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Internal;

/**
 * Measures and slices the text by the characters instead of the bytes.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Exception
 */
final readonly class Text
{
    /**
     * Returns the number of characters of the given value.
     *
     * @return int<0, max>
     */
    public function calculateLength(string $value): int
    {
        $result = \grapheme_strlen($value);

        return \is_int($result) ? \max(0, $result) : \strlen($value);
    }

    /**
     * Returns the given number of characters located at the given offset.
     *
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function slice(string $value, int $offset, int $length): string
    {
        $result = \grapheme_substr($value, $offset, $length);

        return $result === false ? \substr($value, $offset, $length) : $result;
    }
}
