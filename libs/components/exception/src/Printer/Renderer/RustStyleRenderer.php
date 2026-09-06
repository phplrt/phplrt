<?php

declare(strict_types=1);

namespace Phplrt\Exception\Printer\Renderer;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Exception\Analysis\FailureLevel;
use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\Snippet\SourceLine;
use Phplrt\Exception\SnippetReader;

/**
 * Renders the lines of the source code in the way similar to the Rust
 * compiler diagnostics: every line is prefixed by its number and every
 * captured fragment is underlined below the line containing it.
 *
 * A line is printed as long as it is, so the output is as wide as the widest
 * line of the source code it contains.
 *
 * @readonly
 */
abstract class RustStyleRenderer implements RendererInterface
{
    /**
     * @var non-empty-string
     */
    private const GUTTER = ' | ';

    /**
     * @var non-empty-string
     */
    private const ARROW = '--> ';

    /**
     * @var non-empty-string
     */
    private const UNDERLINE = '^';

    public function __construct(
        /**
         * The reader of the source code lines the error is printed along with.
         */
        private readonly SnippetReader $reader = new SnippetReader(),
    ) {}

    /**
     * Creates the renderer the output understands: a terminal that has not
     * been asked to stay plain gets the colors, anything else gets the plain
     * text.
     *
     * @api
     */
    public static function createDefault(): self
    {
        return AnsiRustStyleRenderer::isSupported()
            ? new AnsiRustStyleRenderer()
            : new RawRustStyleRenderer();
    }

    public function render(FailureResult $error, \Throwable $e): string
    {
        $result = [];

        // Every error that has led to this one is drawn above it, so the
        // innermost one comes first and the error itself closes the report
        for ($current = $error; $current !== null; $current = $current->previous) {
            try {
                $result[] = $this->printFailure($current);
            } catch (SourceExceptionInterface) {
                // The source code an error refers to is gone, so there is
                // nothing to show around it and it is left out of the report
                // instead of taking the rest of it down.
            }
        }

        $result = \array_reverse($result);
        $result[] = $e->getTraceAsString();

        return \implode("\n", $result);
    }

    /**
     * Returns the fragment of the source code the given error occurred in,
     * along with everything the error tells about itself.
     *
     * @throws SourceExceptionInterface in case the data of the source cannot
     *         be read
     */
    private function printFailure(FailureResult $error): string
    {
        $lines = \array_values($this->reader->read($error));
        $digits = $this->calculateNumberWidth($lines);

        $result = $this->printHeader($error, $lines, $digits);

        $captured = $this->findCapturedIndex($lines);
        $last = \array_key_last($lines);

        foreach ($lines as $index => $line) {
            foreach ($this->printLine($line, $digits, $error->level, $index === $captured, $index !== $last) as $row) {
                $result[] = $row;
            }
        }

        return \implode("\n", $result);
    }

    /**
     * Prints the part of the output telling about the error of the given
     * severity.
     */
    abstract protected function printError(string $value, FailureLevel $level): string;

    /**
     * Prints the frame around the source code: the numbers of the lines, the
     * gutter separating them from the code and the arrow pointing at the
     * location of the error.
     */
    abstract protected function printFrame(string $value): string;

    /**
     * Prints the visible end of a line, or an empty string in case the end of
     * a line is not shown at all.
     */
    abstract protected function printDelimiter(): string;

    /**
     * @param int<1, max> $digits
     * @param bool $begins the captured fragment starts inside the line
     * @param bool $closed the line is closed by a delimiter
     * @return list<string>
     */
    private function printLine(SourceLine $line, int $digits, FailureLevel $level, bool $begins, bool $closed): array
    {
        [$from, $to] = $this->calculateFragment($line);

        $result = [$this->printRow(
            (string) $line->number,
            $digits,
            $this->highlight($line->value, $from, $to, $level),
            $closed ? $this->printDelimiter() : '',
        )];

        if (!$line instanceof CapturedSourceLine) {
            return $result;
        }

        // The fragment of a zero length points at a position instead of the
        // characters, so it is marked only on the line it starts on
        $underline = $this->printUnderline($from, $to, $begins && $from === $to, $level);

        if ($underline !== null) {
            $result[] = $this->printRow('', $digits, $underline, '');
        }

        return $result;
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

        return $this->printFrame($frame) . $value . $delimiter;
    }

    /**
     * @param int<0, max> $begin
     * @param int<0, max> $end
     * @param bool $points the underline marks a position instead of the characters
     */
    private function printUnderline(int $begin, int $end, bool $points, FailureLevel $level): ?string
    {
        // A line containing no characters of the fragment has nothing
        // to underline
        if ($end <= $begin) {
            if (!$points) {
                return null;
            }

            $end = $begin + 1;
        }

        return \str_repeat(' ', $begin)
            . $this->printError(\str_repeat(self::UNDERLINE, $end - $begin), $level);
    }

    private function printHeaderTitle(FailureResult $error): string
    {
        $name = $error->level->value;

        if ($error->class !== '') {
            $name = \sprintf('%s[%s]', $name, $this->getClassName($error->class));
        }

        $result = $this->printError($name, $error->level);

        if ($error->message !== '') {
            $result .= ': ' . $error->message;
        }

        return $result;
    }

    /**
     * @param list<SourceLine> $lines
     * @param int<1, max> $digits
     */
    private function tryPrintHeaderPathname(FailureResult $error, array $lines, int $digits): ?string
    {
        if (!$error->source instanceof FileInterface) {
            return null;
        }

        $pathname = $error->source->pathname;

        if (($normalized = \realpath($pathname)) !== false) {
            $pathname = $normalized;
        }

        return $this->printFrame(\str_repeat(' ', $digits) . self::ARROW)
            . $pathname
            . $this->printPosition($lines);
    }

    /**
     * @param list<SourceLine> $lines
     * @param int<1, max> $digits
     * @return list<string>
     */
    private function printHeader(FailureResult $error, array $lines, int $digits): array
    {
        $result = [$this->printHeaderTitle($error)];

        if (($pathname = $this->tryPrintHeaderPathname($error, $lines, $digits)) !== null) {
            $result[] = $pathname;
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
                    $this->calculateOffset($line->value, $line->captured->offset) + 1,
                );
            }
        }

        return $lines === [] ? '' : \sprintf(':%d:1', $lines[0]->number);
    }

    /**
     * @param int<0, max> $begin
     * @param int<0, max> $end
     */
    private function highlight(string $value, int $begin, int $end, FailureLevel $level): string
    {
        $length = $this->calculateLength($value);
        $begin = \min($begin, $length);
        $end = \min($end, $length);

        if ($end <= $begin) {
            return $value;
        }

        return $this->slice($value, 0, $begin)
            . $this->printError($this->slice($value, $begin, \max(0, $end - $begin)), $level)
            . $this->slice($value, $end, \max(0, $length - $end));
    }

    /**
     * Returns the offset of the first line containing the captured fragment.
     *
     * @param list<SourceLine> $lines
     */
    private function findCapturedIndex(array $lines): ?int
    {
        foreach ($lines as $index => $line) {
            if ($line instanceof CapturedSourceLine) {
                return $index;
            }
        }

        return null;
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
     * @return array{int<0, max>, int<0, max>} the boundaries of the captured
     *         fragment inside the line, in characters
     */
    private function calculateFragment(SourceLine $line): array
    {
        if (!$line instanceof CapturedSourceLine) {
            return [0, 0];
        }

        $captured = $line->captured;

        return [
            $this->calculateOffset($line->value, $captured->offset),
            $this->calculateOffset($line->value, $captured->endsAt),
        ];
    }

    /**
     * Returns the number of characters located before the given byte offset
     * of the value.
     *
     * @param int<0, max> $offset
     * @return int<0, max>
     */
    private function calculateOffset(string $value, int $offset): int
    {
        return $this->calculateLength(\substr($value, 0, $offset));
    }

    /**
     * Returns the number of characters of the given value.
     *
     * @return int<0, max>
     */
    private function calculateLength(string $value): int
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
    private function slice(string $value, int $offset, int $length): string
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
