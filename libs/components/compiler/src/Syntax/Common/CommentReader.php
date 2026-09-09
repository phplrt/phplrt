<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\Common;

/**
 * Reads the text a comment is written of.
 *
 * A comment is written down again by whatever it is carried into, so what is
 * kept of it is the text alone: the characters wrapping it and the ones
 * repeating on every line belong to the file it has been written in.
 *
 * The text keeps the shape it is written in. Only the single space telling it
 * apart from the star opening a line is dropped, so an indented line stays
 * indented and an empty line stays empty.
 *
 * @readonly
 */
final class CommentReader
{
    /**
     * @var non-empty-string
     */
    private const SEQUENCE_OPENING_DOCBLOCK = '/**';

    /**
     * @var non-empty-string
     */
    private const SEQUENCE_OPENING = '/*';

    /**
     * @var non-empty-string
     */
    private const SEQUENCE_CLOSING = '*/';

    /**
     * @var non-empty-string
     */
    private const SEQUENCE_BODY = '*';

    /**
     * The characters a line is padded with.
     *
     * @var non-empty-string
     */
    private const BLANK = " \t\0\x0B";

    /**
     * The characters the lines of a comment are told apart by.
     *
     * @var non-empty-string
     */
    private const LINE_TERMINATORS = "\r\n";

    /**
     * Returns the text the given comment is written of, or {@see null} in case
     * of the comment says nothing at all.
     *
     * @return non-empty-string|null
     */
    public static function read(string $comment): ?string
    {
        $body = \trim($comment, self::BLANK . self::LINE_TERMINATORS);

        foreach ([self::SEQUENCE_OPENING_DOCBLOCK, self::SEQUENCE_OPENING] as $opening) {
            if (\str_starts_with($body, $opening)) {
                $body = \substr($body, \strlen($opening));

                // Note: The closing sequence belongs to the comment it closes,
                //       so nothing is cut off a text written without one
                if (\str_ends_with($body, self::SEQUENCE_CLOSING)) {
                    $body = \substr($body, 0, -\strlen(self::SEQUENCE_CLOSING));
                }

                break;
            }
        }

        $read = \preg_split('/\R/', $body);

        \assert($read !== false, 'A comment is split by the characters a line ends with');

        $lines = [];

        foreach ($read as $line) {
            $lines[] = self::readLine($line);
        }

        return self::join($lines);
    }

    /**
     * Returns the text the given line of a comment is written of.
     */
    private static function readLine(string $line): string
    {
        $line = \ltrim($line, self::BLANK);

        if (\str_starts_with($line, self::SEQUENCE_BODY)) {
            $line = \substr($line, \strlen(self::SEQUENCE_BODY));

            if (\str_starts_with($line, ' ')) {
                $line = \substr($line, 1);
            }
        }

        return \rtrim($line, self::BLANK);
    }

    /**
     * Returns the given lines as a single text, without the empty ones it
     * begins and ends with.
     *
     * @param list<string> $lines
     * @return non-empty-string|null
     */
    private static function join(array $lines): ?string
    {
        while ($lines !== [] && $lines[0] === '') {
            \array_shift($lines);
        }

        while ($lines !== [] && $lines[\count($lines) - 1] === '') {
            \array_pop($lines);
        }

        $result = \implode("\n", $lines);

        return $result === '' ? null : $result;
    }
}
