<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer;

use Phplrt\Exception\Printer\Internal\LinePart;
use Phplrt\Exception\Printer\Internal\LineWrapper;
use Phplrt\Exception\Printer\Internal\Style\StyleFactory;
use Phplrt\Exception\Printer\Internal\Style\StyleInterface;
use Phplrt\Exception\Printer\Internal\Text;
use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\Snippet\SourceLine;

/**
 * Renders the lines of the source code in the way similar to the Rust
 * compiler diagnostics: every line is prefixed by its number and every
 * captured fragment is underlined below the line containing it.
 */
final readonly class RustStylePrinter implements PrinterInterface
{
    /**
     * The default number of characters available in a single output line.
     *
     * @var int<1, max>
     */
    public const int DEFAULT_WIDTH = 120;

    /**
     * @var non-empty-string
     */
    private const string GUTTER = ' | ';

    /**
     * @var non-empty-string
     */
    private const string ARROW = '--> ';

    /**
     * @var non-empty-string
     */
    private const string ELLIPSIS = '…';

    /**
     * @var non-empty-string
     */
    private const string UNDERLINE = '^';

    private Text $text;

    private LineWrapper $wrapper;

    private StyleInterface $style;

    public function __construct(
        /**
         * The number of characters available in a single output line.
         *
         * @var int<1, max>
         */
        private int $width = self::DEFAULT_WIDTH,
        /**
         * Contains {@see true} in case of the output must be decorated by the
         * escape sequences, {@see false} in case of it must stay plain, or
         * {@see null} in case of the decision belongs to the output itself:
         * a terminal that has not been asked to stay plain gets the colors.
         */
        ?bool $colors = null,
    ) {
        $this->text = new Text();
        $this->wrapper = new LineWrapper($this->text);
        $this->style = StyleFactory::create($colors);
    }

    public function print(iterable $snippets, ?ErrorInfo $info = null): string
    {
        $lines = $this->toArray($snippets);

        $digits = $this->calculateNumberWidth($lines);
        $available = $this->calculateAvailableWidth($digits);

        $level = $info === null ? Level::Error : $info->level;
        $captured = $this->findCapturedIndex($lines);
        $last = \array_key_last($lines);

        $result = $info === null ? [] : $this->printInfo($info, $lines, $digits);

        foreach ($lines as $index => $line) {
            foreach ($this->printLine($line, $digits, $available, $level, $index === $captured, $index !== $last) as $row) {
                $result[] = $row;
            }
        }

        return \implode("\n", $result);
    }

    /**
     * @param int<1, max> $digits
     * @param int<1, max> $available
     * @param bool $begins the captured fragment starts inside the line
     * @param bool $closed the line is closed by a delimiter
     * @return list<string>
     */
    private function printLine(
        SourceLine $line,
        int $digits,
        int $available,
        Level $level,
        bool $begins,
        bool $closed,
    ): array {
        [$from, $to] = $this->calculateFragment($line);

        $parts = $this->wrapper->split($line->value, $available, $this->text->calculateLength(self::ELLIPSIS));
        $closing = \array_key_last($parts);

        $result = [];

        foreach ($parts as $key => $part) {
            $until = $part->from + $part->size;

            $begin = \max(0, \min($from, $until) - $part->from);
            $end = \max(0, \min($to, $until) - $part->from);

            $result[] = $this->printRow(
                $key === 0 ? (string) $line->number : '',
                $digits,
                $this->printPart($part, $begin, $end, $level),
                $closed && $key === $closing ? $this->style->dim($this->style->getDelimiter()) : '',
            );

            if (!$line instanceof CapturedSourceLine) {
                continue;
            }

            // The fragment of a zero length points to a position instead of the
            // characters, so it is marked only inside the part containing it
            $points = $begins && $from === $to && $part->contains($from);

            $underline = $this->printUnderline($part->indent + $begin, $part->indent + $end, $available, $points, $level);

            if ($underline !== null) {
                $result[] = $this->printRow('', $digits, $underline, '');
            }
        }

        return $result;
    }

    /**
     * @param int<0, max> $begin
     * @param int<0, max> $end
     */
    private function printPart(LinePart $part, int $begin, int $end, Level $level): string
    {
        $mark = $this->style->dim(self::ELLIPSIS);

        return ($part->indent > 0 ? $mark : '')
            . $this->highlight($part->value, $begin, $end, $level)
            . ($part->continued ? $mark : '');
    }

    /**
     * @param int<1, max> $digits
     */
    private function printRow(string $number, int $digits, string $value, string $delimiter): string
    {
        $frame = \str_pad($number, $digits, ' ', \STR_PAD_LEFT) . self::GUTTER;

        if ($delimiter === '') {
            $value = \rtrim($value);

            // Nothing follows the gutter, so it ends the row itself
            if ($value === '') {
                $frame = \rtrim($frame);
            }
        }

        return $this->style->frame($frame) . $value . $delimiter;
    }

    /**
     * @param int<0, max> $begin
     * @param int<0, max> $end
     * @param int<1, max> $available
     * @param bool $points the underline marks a position instead of the characters
     */
    private function printUnderline(int $begin, int $end, int $available, bool $points, Level $level): ?string
    {
        // A part containing no characters of the fragment has nothing
        // to underline
        if ($end <= $begin) {
            if (!$points) {
                return null;
            }

            $begin = \min($begin, $available - 1);
            $end = $begin + 1;
        }

        return \str_repeat(' ', $begin)
            . $this->style->paint(\str_repeat(self::UNDERLINE, $end - $begin), $level);
    }

    /**
     * @param list<SourceLine> $lines
     * @param int<1, max> $digits
     * @return list<string>
     */
    private function printInfo(ErrorInfo $info, array $lines, int $digits): array
    {
        $result = [];

        if ($info->message !== null) {
            $name = $info->class === null
                ? $info->level->value
                : \sprintf('%s[%s]', $info->level->value, $this->getClassName($info->class));

            $title = \sprintf('%s: %s', $name, $info->message);

            // The message is the only thing telling what has happened, so it
            // is carried over to the next lines instead of being cut off
            foreach ($this->wrap($title, \max(1, $this->width)) as $index => $line) {
                $result[] = $index === 0 && \str_starts_with($line, $name)
                    ? $this->style->paint($name, $info->level) . \substr($line, \strlen($name))
                    : $line;
            }
        }

        if ($info->pathname !== null) {
            $location = $info->pathname . $this->printPosition($lines);
            $available = \max(1, $this->width - $digits - $this->text->calculateLength(self::ARROW));

            $result[] = $this->style->frame(\str_repeat(' ', $digits) . self::ARROW)
                . $this->cut($location, $available);
        }

        if ($result !== [] && $lines !== []) {
            $result[] = $this->style->frame(\str_repeat(' ', $digits) . \rtrim(self::GUTTER));
        }

        return $result;
    }

    /**
     * Splits the value into the lines fitting into the available width,
     * breaking it between the words wherever it is possible.
     *
     * @param int<1, max> $width
     * @return non-empty-list<string>
     */
    private function wrap(string $value, int $width): array
    {
        $result = [];
        $line = '';

        foreach (\explode(' ', $value) as $word) {
            $merged = $line === '' ? $word : $line . ' ' . $word;

            if ($this->text->calculateLength($merged) <= $width) {
                $line = $merged;

                continue;
            }

            if ($line !== '') {
                $result[] = $line;
            }

            // A word that does not fit into the width on its own is the only
            // case the value is broken in the middle of it
            while (($length = $this->text->calculateLength($word)) > $width) {
                $result[] = $this->text->slice($word, 0, $width);
                $word = $this->text->slice($word, $width, \max(0, $length - $width));
            }

            $line = $word;
        }

        if ($line !== '' || $result === []) {
            $result[] = $line;
        }

        return $result;
    }

    /**
     * Returns the name of the class without the namespace it belongs to, so
     * that a long namespace does not take the room the message needs.
     */
    private function getClassName(string $class): string
    {
        $offset = \strrpos($class, '\\');

        return $offset === false ? $class : \substr($class, $offset + 1);
    }

    /**
     * Returns the position of the captured fragment inside the source code.
     *
     * @param list<SourceLine> $lines
     */
    private function printPosition(array $lines): string
    {
        foreach ($lines as $line) {
            if ($line instanceof CapturedSourceLine) {
                return \sprintf(
                    ':%d:%d',
                    $line->number,
                    $this->calculateOffset($line->value, $line->startColumn) + 1,
                );
            }
        }

        return $lines === [] ? '' : \sprintf(':%d:1', $lines[0]->number);
    }

    /**
     * Returns the beginning of the value fitting into the available width.
     *
     * @param int<1, max> $available
     */
    private function cut(string $value, int $available): string
    {
        if ($this->text->calculateLength($value) <= $available) {
            return $value;
        }

        $inner = $available - $this->text->calculateLength(self::ELLIPSIS);

        // The mark is omitted in case it leaves no room for the value itself
        if ($inner < 1) {
            return $this->text->slice($value, 0, $available);
        }

        return $this->text->slice($value, 0, $inner) . $this->style->dim(self::ELLIPSIS);
    }

    /**
     * @param int<0, max> $begin
     * @param int<0, max> $end
     */
    private function highlight(string $value, int $begin, int $end, Level $level): string
    {
        $length = $this->text->calculateLength($value);
        $begin = \min($begin, $length);
        $end = \min($end, $length);

        if ($end <= $begin) {
            return $value;
        }

        return $this->text->slice($value, 0, $begin)
            . $this->style->paint($this->text->slice($value, $begin, \max(0, $end - $begin)), $level)
            . $this->text->slice($value, $end, \max(0, $length - $end));
    }

    /**
     * @param iterable<mixed, SourceLine> $snippets
     * @return list<SourceLine>
     */
    private function toArray(iterable $snippets): array
    {
        return $snippets instanceof \Traversable
            ? \iterator_to_array($snippets, false)
            : \array_values($snippets);
    }

    /**
     * Returns the offset of the first line containing the captured fragment.
     *
     * @param list<SourceLine> $lines
     */
    private function findCapturedIndex(array $lines): ?int
    {
        return \array_find_key($lines, static fn(SourceLine $line): bool => $line instanceof CapturedSourceLine);
    }

    /**
     * @param list<SourceLine> $lines
     * @return int<1, max>
     */
    private function calculateNumberWidth(array $lines): int
    {
        $result = 1;

        foreach ($lines as $line) {
            $result = \max($result, \strlen((string) $line->number));
        }

        return $result;
    }

    /**
     * @param int<1, max> $digits
     * @return int<1, max>
     */
    private function calculateAvailableWidth(int $digits): int
    {
        return \max(1, $this->width
            - $digits
            - $this->text->calculateLength(self::GUTTER)
            - $this->text->calculateLength($this->style->getDelimiter()));
    }

    /**
     * @return array{int<0, max>, int<0, max>} the boundaries of the captured
     *         fragment inside the line, in characters
     */
    private function calculateFragment(SourceLine $line): array
    {
        if (!$line instanceof CapturedSourceLine) {
            return [0, 0];
        }

        return [
            $this->calculateOffset($line->value, $line->startColumn),
            $this->calculateOffset($line->value, $line->startColumn + $line->width),
        ];
    }

    /**
     * Returns the offset of the character located at the given byte column.
     *
     * @param int<1, max> $column
     * @return int<0, max>
     */
    private function calculateOffset(string $value, int $column): int
    {
        return $this->text->calculateLength(\substr($value, 0, $column - 1));
    }
}
