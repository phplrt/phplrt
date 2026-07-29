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
        if (\function_exists('\\grapheme_strlen')) {
            $result = \grapheme_strlen($value);

            if (\is_int($result)) {
                return $result;
            }
        }

        if (\function_exists('\\mb_strlen')) {
            return \mb_strlen($value);
        }

        return \strlen($value);
    }

    /**
     * Returns the given number of characters located at the given offset.
     *
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function slice(string $value, int $offset, int $length): string
    {
        if (\function_exists('\\grapheme_substr')) {
            $result = \grapheme_substr($value, $offset, $length);

            if ($result !== false) {
                return $result;
            }
        }

        if (\function_exists('\\mb_substr')) {
            return \mb_substr($value, $offset, $length);
        }

        return \substr($value, $offset, $length);
    }
}
