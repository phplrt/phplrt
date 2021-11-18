<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Printer;

final class PrinterCreateInfo
{
    /**
     * @var positive-int
     */
    public const DEFAULT_LENGTH = 32;

    /**
     * @see https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.double
     * @var array<non-empty-string, non-empty-string>
     */
    public const DEFAULT_REPLACEMENTS = [
        "\n" => '\n',
        "\r" => '\r',
        "\t" => '\t',
        "\v" => '\v',
        "\e" => '\e',
        "\f" => '\f',
        "\0" => '\0',
    ];

    /**
     * The maximum length of the token value, above which this value will be
     * truncated.
     *
     * @var positive-int
     */
    public readonly int $length;

    /**
     * List of direct character replacements for displaying special characters.
     *
     * For example, in the case when the control LINE FEED character with the
     * 0x0A code must be replaced with the combination "\n" when rendering, then
     * the value of this field will contain [ "\u{000A}" => '\n' ] array.
     *
     * @var array<non-empty-string, non-empty-string>
     */
    public readonly array $replace;

    /**
     * @param positive-int $length
     *        See also {@see PrinterCreateInfo::$length}
     * @param iterable<non-empty-string, non-empty-string> $replace
     *        See also {@see PrinterCreateInfo::$replace}
     */
    public function __construct(
        int $length = self::DEFAULT_LENGTH,
        iterable $replace = self::DEFAULT_REPLACEMENTS,
    ) {
        // Initialize string truncate length
        $this->length = $this->formatLength($length);

        // Initialize string replacements
        $this->replace = $this->formatReplacements($replace);
    }

    /**
     * @param int $length
     * @return positive-int
     */
    private function formatLength(int $length): int
    {
        assert($length > 0, new \InvalidArgumentException(
            'Truncated string length MUST be greater than 0'
        ));

        return $length;
    }

    /**
     * @param iterable<string, string> $replace
     * @return array<non-empty-string, non-empty-string>
     */
    private function formatReplacements(iterable $replace): array
    {
        $result = [];

        foreach ($replace as $from => $to) {
            assert(\is_string($from) && $from !== '', new \InvalidArgumentException(
                'Replacement "from" must be a non-empty string'
            ));

            assert(\is_string($to) && $to !== '', new \InvalidArgumentException(
                'Replacement "to" must be a non-empty string'
            ));

            $result[$from] = $to;
        }

        return $result;
    }
}
