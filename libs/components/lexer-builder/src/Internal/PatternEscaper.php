<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Internal;

/**
 * Escapes the delimiter a pattern is written between, so that PHP does not
 * take it for the end of the pattern.
 *
 * A delimiter escaped by the pattern itself already means itself, so it is
 * left as it is, and escaping a pattern twice changes nothing: a pattern
 * written as "a\/b" means the very same thing as "a/b", and escaping it once
 * more would turn it into a backslash followed by the delimiter.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Lexer\Builder
 */
final class PatternEscaper
{
    /**
     * @param non-empty-string $delimiter
     * @return ($pattern is non-empty-string ? non-empty-string : string)
     */
    public static function escape(string $pattern, string $delimiter): string
    {
        $result = '';

        for ($offset = 0, $length = \strlen($pattern); $offset < $length; ++$offset) {
            $char = $pattern[$offset];

            // Note: Everything between "\Q" and "\E" means itself, a backslash
            //       included, so a character is escaped there by leaving the
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
