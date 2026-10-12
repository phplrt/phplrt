<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Internal;

/**
 * Finds the string literals of PHP code, the text of which is a value rather
 * than code, so that laying the code out leaves them as they are.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Compiler
 */
final class PhpStringLiterals
{
    /**
     * Opens the code read on its own, which is a piece of a PHP file rather
     * than a file of its own.
     */
    private const FRAGMENT_PREFIX = '<?php ';

    /**
     * Returns the string literals of the given code as the offset each of them
     * starts at, its length, and whether it is a heredoc (or a nowdoc).
     *
     * A quoted literal is returned along with its quotes, and a heredoc along
     * with the lines opening and closing it.
     *
     * @param bool $isFragment whether the code is a piece of a PHP file rather
     *        than a file of its own
     * @return list<array{int<0, max>, int<0, max>, bool}>
     */
    public static function findLiterals(string $code, bool $isFragment = false): array
    {
        $prefix = $isFragment ? self::FRAGMENT_PREFIX : '';
        $shift = \strlen($prefix);

        $result = [];

        $start = null;
        $isHeredoc = false;

        foreach (\PhpToken::tokenize($prefix . $code) as $token) {
            $offset = $token->pos - $shift;

            if ($start === null) {
                if ($token->is(\T_CONSTANT_ENCAPSED_STRING)) {
                    $result[] = [$offset, \strlen($token->text), false];
                } elseif ($token->is(\T_START_HEREDOC)) {
                    [$start, $isHeredoc] = [$offset, true];
                } elseif ($token->is(['"', '`'])) {
                    [$start, $isHeredoc] = [$offset, false];
                }
            } elseif ($token->is($isHeredoc ? \T_END_HEREDOC : ['"', '`'])) {
                $result[] = [$start, $offset + \strlen($token->text) - $start, $isHeredoc];

                $start = null;
            }
        }

        // A literal left open runs up to the end of the code
        if ($start !== null) {
            $result[] = [$start, \strlen($code) - $start, $isHeredoc];
        }

        /** @var list<array{int<0, max>, int<0, max>, bool}> */
        return $result;
    }

    /**
     * Returns the numbers of the lines of the given code that start inside a
     * string literal, so that their leading spaces are a part of its value.
     *
     * @param bool $isFragment whether the code is a piece of a PHP file rather
     *        than a file of its own
     * @param bool $isHeredocIncluded whether a heredoc (or a nowdoc) is one of
     *        the literals, along with the line closing it
     * @return array<int<0, max>, true>
     */
    public static function findLiteralLines(
        string $code,
        bool $isFragment = false,
        bool $isHeredocIncluded = true,
    ): array {
        $result = [];

        foreach (self::findLiterals($code, $isFragment) as [$start, $length, $isHeredoc]) {
            if ($isHeredoc && !$isHeredocIncluded) {
                continue;
            }

            $first = \substr_count($code, "\n", 0, $start);
            $count = \substr_count($code, "\n", $start, $length);

            // Every line break of a literal is followed by more of it, be it
            // the closing quote or the line closing a heredoc
            for ($line = $first + 1; $line <= $first + $count; ++$line) {
                $result[$line] = true;
            }
        }

        return $result;
    }
}
