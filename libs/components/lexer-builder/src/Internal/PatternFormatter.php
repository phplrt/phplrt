<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Internal;

use Phplrt\Lexer\Builder\Definition\RegexModifier;

/**
 * Writes the expression of a token down as a PCRE pattern.
 *
 * Whatever hands an expression over to PCRE, be it the pattern of the whole
 * lexer or a check of a single token, writes it down the very same way, so
 * that the expression means the same thing everywhere it is compiled.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Lexer\Builder
 */
final class PatternFormatter
{
    /**
     * PCRE delimiter the patterns are written between, unless told otherwise.
     */
    public const DEFAULT_DELIMITER = '/';

    /**
     * Ends whatever the expression of a token leaves open until the end of
     * the expression, so that it ends along with the token rather than at the
     * end of the whole pattern the token is written into.
     *
     * @param non-empty-string $regex
     * @param iterable<array-key, RegexModifier> $flags
     * @return non-empty-string
     */
    public static function formatExpression(string $regex, iterable $flags): string
    {
        // Note: A text quoted by "\Q" lasts until "\E", and a "\E" quoting
        //       nothing is ignored.
        if (\str_contains($regex, '\\Q')) {
            $regex .= '\\E';
        }

        // Note: A comment of an extended expression lasts until the end of
        //       the line, and a line break is ignored by such an expression.
        foreach ($flags as $flag) {
            if ($flag === RegexModifier::Extended) {
                return $regex . "\n";
            }
        }

        return $regex;
    }

    /**
     * Writes the given expression down between the delimiters, along with
     * the flags it is compiled with.
     *
     * @param iterable<array-key, RegexModifier> $flags
     * @param non-empty-string $delimiter
     * @return non-empty-string
     */
    public static function formatPattern(
        string $regex,
        iterable $flags,
        string $delimiter = self::DEFAULT_DELIMITER,
    ): string {
        $modifiers = '';

        foreach ($flags as $flag) {
            $modifiers .= $flag->value;
        }

        return $delimiter . self::escapeDelimiter($regex, $delimiter) . $delimiter . $modifiers;
    }

    /**
     * Escapes the delimiter a pattern is written between, so that PHP does not
     * take it for the end of the pattern.
     *
     * A delimiter escaped by the pattern itself already means itself, so it is
     * left as it is, and escaping a pattern twice changes nothing: a pattern
     * written as "a\/b" means the very same thing as "a/b", and escaping it
     * once more would turn it into a backslash followed by the delimiter.
     *
     * @param non-empty-string $delimiter
     * @return ($pattern is non-empty-string ? non-empty-string : string)
     */
    public static function escapeDelimiter(string $pattern, string $delimiter = self::DEFAULT_DELIMITER): string
    {
        $result = '';

        for ($offset = 0, $length = \strlen($pattern); $offset < $length; ++$offset) {
            $char = $pattern[$offset];

            // Note: Everything between "\Q" and "\E" means itself, a backslash
            //       included, so the delimiter is escaped there by leaving the
            //       quoted text for a moment: "\Q/\E" becomes "\Q\E\/\Q\E".
            if ($char === '\\' && ($pattern[$offset + 1] ?? '') === 'Q') {
                $result .= '\\Q';

                for ($offset += 2; $offset < $length; ++$offset) {
                    $quoted = $pattern[$offset];

                    if ($quoted === '\\' && ($pattern[$offset + 1] ?? '') === 'E') {
                        break;
                    }

                    $result .= $quoted === $delimiter
                        ? '\\E\\' . $quoted . '\\Q'
                        : $quoted;
                }

                $result .= '\\E';
                ++$offset;

                continue;
            }

            // Note: The character after a backslash is taken along with it,
            //       the way PHP itself looks for the closing delimiter, so
            //       "\\/" stays a backslash followed by an escaped delimiter.
            if ($char === '\\') {
                $result .= $char . ($pattern[++$offset] ?? '');

                continue;
            }

            if ($char === $delimiter) {
                $result .= '\\';
            }

            $result .= $char;
        }

        return $result;
    }
}
