<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Codec;

/**
 * The VLQ is a Base64 value, where the most significant bit (the 6th bit) is
 * used as the continuation bit, and the “digits” are encoded into the string
 * least significant first, and where the least significant bit of the first
 * digit is used as the sign bit.
 *
 * Note: The values that can be represent by the VLQ Base64 encoded are limited
 * to 32 bit quantities until some use case for larger values is presented.
 *
 * @link https://en.wikipedia.org/wiki/Variable-length_quantity
 * @link https://en.wikipedia.org/wiki/Base64
 *
 * Based on the Base 64 VLQ implementation in Closure Compiler:
 * @link https://github.com/google/closure-compiler/blob/v20211107/src/com/google/debugging/sourcemap/Base64VLQ.java
 *
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\SourceMap\Codec
 */
final class Base64Vlq implements CodecInterface
{
    /**
     * A Base64 VLQ digit can represent 5 bits, so it is base-32.
     *
     * @var int
     */
    private const VLQ_BASE_SHIFT = 5;

    /**
     * @var int
     */
    private const VLQ_BASE = 1 << self::VLQ_BASE_SHIFT;

    /**
     * A mask of bits for a VLQ digit (11111), 31 decimal.
     *
     * @var int
     */
    private const VLQ_BASE_MASK = self::VLQ_BASE - 1;

    /**
     * The continuation bit is the 6th bit.
     *
     * @var int
     */
    private const VLQ_CONTINUATION_BIT = self::VLQ_BASE;

    /**
     * @var int
     */
    private const INT32_MIN = -0x8000_0000;

    /**
     * @var int
     */
    private const INT32_MAX = 0x7FFF_FFFF;

    /**
     * @var string
     */
    private const ERROR_ENCODE_INVALID_ARGUMENT =
        'Passed argument must be an array<int>, but array<int|%s> given';

    /**
     * @var string
     */
    private const ERROR_ENCODE_INT32 =
        'Passed argument must be an array of int32, ' .
        'but passed %d is not a valid int32 value';

    /**
     * @var string
     */
    private const ERROR_DECODE_INVALID_ARGUMENT =
        'Passed argument must be a string that contains one of "' . self::DICTIONARY .
        '" character, but "%s" given';

    /**
     * @var non-empty-string
     */
    private const DICTIONARY = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';

    /**
     * @var array<int, non-empty-string>
     */
    private static array $intToChar = [];

    /**
     * @var array<non-empty-string, int>
     */
    private static array $charToInt;

    /**
     * @psalm-suppress PropertyTypeCoercion
     */
    public function __construct()
    {
        if (self::$intToChar === []) {
            self::$intToChar = \str_split(self::DICTIONARY);
            self::$charToInt = \array_flip(self::$intToChar);
        }
    }

    /**
     * Returns a VLQ encoded value as a string (char array).
     *
     * {@inheritDoc}
     */
    public function encode(iterable $values): string
    {
        $result = '';

        foreach ($values as $value) {
            assert(\is_int($value), new \InvalidArgumentException(
                \sprintf(self::ERROR_ENCODE_INVALID_ARGUMENT, \get_debug_type($value))
            ));

            assert($value >= self::INT32_MIN && $value <= self::INT32_MAX, new \OutOfRangeException(
                \sprintf(self::ERROR_ENCODE_INT32, $value)
            ));

            /** @psalm-suppress ArgumentTypeCoercion */
            $result .= $this->encodeInt($value);
        }

        return $result;
    }

    /**
     * @param positive-int|0 $num
     * @return string
     */
    private function encodeInt(int $num): string
    {
        $result = '';

        // Converts from a two-complement value to a value where the sign bit is
        // is placed in the least significant bit.
        //
        // For example, as decimals:
        //   1 becomes 2 (10 binary), -1 becomes 3 (11 binary)
        //   2 becomes 4 (100 binary), -2 becomes 5 (101 binary)
        $num = $num < 0 ? (-$num << 1) | 1 : $num << 1;

        do {
            $digit = $num & self::VLQ_BASE_MASK;
            // Raw implementation of unsigned int 32 right shift:
            // $num >>>= 5
            $num = ($num >> self::VLQ_BASE_SHIFT) & ~(1 << (self::VLQ_BASE - 1) >> (self::VLQ_BASE_SHIFT - 1));

            if ($num > 0) {
                $digit |= self::VLQ_CONTINUATION_BIT;
            }

            $result .= self::$intToChar[$digit];
        } while ($num > 0);

        return $result;
    }

    /**
     * Decodes the VLQ values from the provided string (char array).
     *
     * {@inheritDoc}
     */
    public function decode(string $string): array
    {
        $result = [];
        $shift = $value = 0;
        for ($i = 0, $length = \strlen($string); $i < $length; ++$i) {
            $index = self::$charToInt[$string[$i]] ?? null;

            assert(\is_int($index), new \InvalidArgumentException(
                \sprintf(self::ERROR_DECODE_INVALID_ARGUMENT, $string[$i])
            ));

            $isContinuation = ($index & self::VLQ_CONTINUATION_BIT) !== 0;

            $index &= self::VLQ_BASE_MASK;
            $value += $index << $shift;

            if ($isContinuation) {
                $shift += self::VLQ_BASE_SHIFT;

                continue;
            }

            $isNegative = $value & 1;
            $value = ($value >> 1) & ~(1 << self::VLQ_BASE - 1);

            if ($isNegative) {
                $value = $value === 0 ? self::INT32_MIN : -$value;
            }

            $result[] = $value;
            $value = $shift = 0;
        }

        return $result;
    }
}
