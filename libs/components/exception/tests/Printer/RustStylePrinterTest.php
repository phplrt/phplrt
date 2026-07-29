<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Printer;

use Phplrt\Exception\Printer\ErrorInfo;
use Phplrt\Exception\Printer\Level;
use Phplrt\Exception\Printer\RustStylePrinter;
use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\Snippet\Reader\Content\StringContent;
use Phplrt\Exception\Snippet\Reader\SourceLineReader;
use Phplrt\Exception\Snippet\SourceLine;
use Phplrt\Exception\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class RustStylePrinterTest extends TestCase
{
    #[TestDox('Every line is prefixed by its number and the captured one is underlined')]
    public function testPrintsLinesWithTheUnderlinedFragment(): void
    {
        $printer = new RustStylePrinter();
        $reader = new SourceLineReader();

        self::assertSame(<<<'OUT'
            2 | line 2
            3 | line 3
            4 | line 4
              |   ^^
            5 | line 5
            6 | line 6
            OUT, $printer->print($reader->read(new StringContent(self::createSource()), 23, 2, 2)));
    }

    #[TestDox('The line numbers are aligned to the widest one')]
    public function testAlignsLineNumbers(): void
    {
        $printer = new RustStylePrinter();
        $reader = new SourceLineReader();

        $source = \implode("\n", \array_map(static fn(int $i): string => 'line ' . $i, \range(1, 12)));

        self::assertSame(<<<'OUT'
             8 | line 8
             9 | line 9
            10 | line 10
               | ^^^^^^^
            11 | line 11
            12 | line 12
            OUT, $printer->print($reader->read(new StringContent($source), 63, 7, 2)));
    }

    #[TestDox('The multi-byte characters are underlined as single ones')]
    public function testUnderlinesCharactersInsteadOfBytes(): void
    {
        $printer = new RustStylePrinter();

        $value = 'Hello Вася';
        $offset = \strpos($value, 'Вася');

        self::assertIsInt($offset);

        $lines = [new CapturedSourceLine(1, 0, $value, $offset + 1, \strlen('Вася'))];

        self::assertSame(<<<'OUT'
            1 | Hello Вася
              |       ^^^^
            OUT, $printer->print($lines));
    }

    #[TestDox('An empty fragment is underlined by a single character')]
    public function testUnderlinesEmptyFragment(): void
    {
        $printer = new RustStylePrinter();

        self::assertSame(<<<'OUT'
            1 | line 1
              |    ^
            OUT, $printer->print([new CapturedSourceLine(1, 0, 'line 1', 4, 0)]));
    }

    #[TestDox('Every line of a multi-line fragment is underlined')]
    public function testUnderlinesEveryLineOfTheFragment(): void
    {
        $printer = new RustStylePrinter();
        $reader = new SourceLineReader();

        self::assertSame(<<<'OUT'
            3 | line 3
            4 | line 4
              |      ^
            5 | line 5
              | ^^^^^^
            6 | line 6
              | ^^^^
            7 | line 7
            OUT, $printer->print($reader->read(new StringContent(self::createSource()), 26, 13, 1)));
    }

    #[TestDox('A line exceeding the available width is wrapped')]
    public function testWrapsLineExceedingTheWidth(): void
    {
        $printer = new RustStylePrinter(width: 16);

        $value = \str_repeat('a', 9) . 'ERROR' . \str_repeat('b', 3);

        $result = $printer->print([new CapturedSourceLine(1, 0, $value, 10, 5)]);

        self::assertSame(<<<'OUT'
            1 | aaaaaaaaaER…
              |          ^^
              | …RORbbb
              |  ^^^
            OUT, $result);

        foreach (\explode("\n", $result) as $line) {
            self::assertLessThanOrEqual(16, \grapheme_strlen($line));
        }
    }

    #[TestDox('The fragment is underlined on every line it is wrapped to')]
    public function testUnderlinesEveryPartOfTheWrappedFragment(): void
    {
        $printer = new RustStylePrinter(width: 16);

        $value = 'aaaERRORbbbERRORccc';

        self::assertSame(<<<'OUT'
            1 | aaaERRORbbb…
              |    ^^^^^^^^
              | …ERRORccc
              |  ^^^^^
            OUT, $printer->print([new CapturedSourceLine(1, 0, $value, 4, 13)]));
    }

    #[TestDox('The width leaving no room for the markers does not break the output')]
    public function testPrintsWithinAnUnreachableWidth(): void
    {
        $printer = new RustStylePrinter(width: 6);

        self::assertSame(<<<'OUT'
            1 | li
              | ^^
              | ne
              | ^^
              |  1
              | ^^
            OUT, $printer->print([new CapturedSourceLine(1, 0, 'line 1', 1, 6)]));
    }

    #[TestDox('The lines without a captured fragment are printed as is')]
    public function testPrintsLinesWithoutTheCapturedFragment(): void
    {
        $printer = new RustStylePrinter();

        self::assertSame(<<<'OUT'
            1 | line 1
            2 | line 2
            OUT, $printer->print([new SourceLine(1, 0, 'line 1'), new SourceLine(2, 7, 'line 2')]));
    }

    #[TestDox('The empty line is printed without the trailing whitespaces')]
    public function testPrintsEmptyLine(): void
    {
        $printer = new RustStylePrinter();

        self::assertSame("1 |\n2 | line 2", $printer->print([
            new SourceLine(1, 0, ''),
            new SourceLine(2, 1, 'line 2'),
        ]));
    }

    #[TestDox('The empty list of lines is printed as an empty string')]
    public function testPrintsEmptyList(): void
    {
        $printer = new RustStylePrinter();

        self::assertSame('', $printer->print([]));
    }

    #[TestDox('The line without characters of the fragment is not underlined')]
    public function testDoesNotUnderlineLineWithoutCharactersOfTheFragment(): void
    {
        $printer = new RustStylePrinter();
        $reader = new SourceLineReader();

        self::assertSame(<<<'OUT'
            1 | line 1
              |    ^^^
            2 |
            3 | line 3
              | ^^^^^^
            OUT, $printer->print($reader->read(new StringContent("line 1\n\nline 3"), 3, 12, 0)));
    }

    #[TestDox('The error message is printed above the source code')]
    public function testPrintsErrorMessage(): void
    {
        $printer = new RustStylePrinter();

        self::assertSame(<<<'OUT'
            error: Unexpected token
              |
            1 | line 1
              |    ^^^
            OUT, $printer->print(
            [new CapturedSourceLine(1, 0, 'line 1', 4, 3)],
            new ErrorInfo(message: 'Unexpected token'),
        ));
    }

    #[TestDox('The error location is printed above the source code')]
    public function testPrintsErrorLocation(): void
    {
        $printer = new RustStylePrinter();

        self::assertSame(<<<'OUT'
            error[LogicException]: Unexpected token
              --> /app/example.php:42:4
               |
            42 | line 1
               |    ^^^
            OUT, $printer->print(
            [new CapturedSourceLine(42, 0, 'line 1', 4, 3)],
            new ErrorInfo(
                message: 'Unexpected token',
                pathname: '/app/example.php',
                class: \LogicException::class,
            ),
        ));
    }

    #[TestDox('The column of the error location is counted in characters')]
    public function testPrintsErrorLocationColumnInCharacters(): void
    {
        $printer = new RustStylePrinter();

        $value = 'Привет Вася';
        $offset = \strpos($value, 'Вася');

        self::assertIsInt($offset);

        self::assertSame(<<<'OUT'
             --> /app/example.php:1:8
              |
            1 | Привет Вася
              |        ^^^^
            OUT, $printer->print(
            [new CapturedSourceLine(1, 0, $value, $offset + 1, \strlen('Вася'))],
            new ErrorInfo(pathname: '/app/example.php'),
        ));
    }

    #[TestDox('The message exceeding the available width is carried over to the next line')]
    public function testWrapsErrorMessage(): void
    {
        $printer = new RustStylePrinter(width: 24);

        self::assertSame(<<<'OUT'
            error: Message that is
            too long to be printed
             --> /a/very/very/very/…
              |
            1 | line 1
              | ^^^^
            OUT, $printer->print(
            [new CapturedSourceLine(1, 0, 'line 1', 1, 4)],
            new ErrorInfo(
                message: 'Message that is too long to be printed',
                pathname: '/a/very/very/very/very/deep/app.php',
            ),
        ));
    }

    #[TestDox('A word exceeding the available width on its own is broken in the middle')]
    public function testWrapsUnbreakableErrorMessage(): void
    {
        $printer = new RustStylePrinter(width: 12);

        self::assertSame(<<<'OUT'
            error: Some
            unpronouncea
            bleword is
            here
              |
            1 | line 1
              | ^^^^
            OUT, $printer->print(
            [new CapturedSourceLine(1, 0, 'line 1', 1, 4)],
            new ErrorInfo(message: 'Some unpronounceableword is here'),
        ));
    }

    #[TestDox('The class the error is named by is printed without the namespace it belongs to')]
    public function testPrintsShortClassName(): void
    {
        $printer = new RustStylePrinter();

        self::assertSame(<<<'OUT'
            error[UnexpectedTokenException]: Something went wrong
              |
            1 | line 1
              | ^^^^
            OUT, $printer->print(
            [new CapturedSourceLine(1, 0, 'line 1', 1, 4)],
            new ErrorInfo(
                message: 'Something went wrong',
                class: 'Phplrt\Parser\Exception\UnexpectedTokenException',
            ),
        ));
    }

    #[TestDox('The error information is printed without the source code')]
    public function testPrintsErrorInformationWithoutSourceCode(): void
    {
        $printer = new RustStylePrinter();

        self::assertSame(
            'error: Unexpected end of input',
            $printer->print([], new ErrorInfo(message: 'Unexpected end of input')),
        );
    }

    #[TestDox('The severity of the error is printed instead of the default one')]
    public function testPrintsErrorLevel(): void
    {
        $printer = new RustStylePrinter();

        self::assertSame(<<<'OUT'
            warning: Unused variable
              |
            1 | line 1
              | ^^^^
            OUT, $printer->print(
            [new CapturedSourceLine(1, 0, 'line 1', 1, 4)],
            new ErrorInfo(message: 'Unused variable', level: Level::Warning),
        ));
    }

    #[TestDox('The error is not highlighted until the colors are enabled')]
    public function testDoesNotHighlightErrorByDefault(): void
    {
        $printer = new RustStylePrinter(colors: false);

        self::assertStringNotContainsString("\e", self::printExample($printer));
    }

    #[TestDox('The output asked to stay plain is not highlighted')]
    public function testDoesNotHighlightPlainOutput(): void
    {
        // The "NO_COLOR" variable is set by the configuration of the tests
        $printer = new RustStylePrinter();

        self::assertStringNotContainsString("\e", self::printExample($printer));
    }

    #[TestDox('The output asked to print the colors is highlighted')]
    public function testHighlightsForcedOutput(): void
    {
        self::withEnv(['NO_COLOR' => null, 'FORCE_COLOR' => '1'], static function (): void {
            $printer = new RustStylePrinter();

            self::assertStringContainsString("\e", self::printExample($printer));
        });
    }

    #[TestDox('The decision of the caller wins over the one of the output')]
    public function testExplicitColorsWinOverTheOutput(): void
    {
        // The "NO_COLOR" variable is set by the configuration of the tests
        $printer = new RustStylePrinter(colors: true);

        self::assertStringContainsString("\e", self::printExample($printer));
    }

    private static function printExample(RustStylePrinter $printer): string
    {
        return $printer->print(
            [new CapturedSourceLine(1, 0, 'line 1', 3, 2)],
            new ErrorInfo(message: 'Oops'),
        );
    }

    /**
     * Runs the callback with the given environment variables, restoring
     * everything it has changed afterwards.
     *
     * @param array<non-empty-string, string|null> $variables
     */
    private static function withEnv(array $variables, \Closure $then): void
    {
        $previous = [];

        foreach ($variables as $name => $value) {
            $previous[$name] = \getenv($name);

            $value === null ? \putenv($name) : \putenv($name . '=' . $value);
        }

        try {
            $then();
        } finally {
            foreach ($previous as $name => $value) {
                $value === false ? \putenv($name) : \putenv($name . '=' . $value);
            }
        }
    }

    /**
     * @param non-empty-string $sequence
     */
    #[TestDox('The severity, the captured fragment and its underline are highlighted')]
    #[DataProvider('levelsDataProvider')]
    public function testHighlightsError(Level $level, string $sequence): void
    {
        $printer = new RustStylePrinter(colors: true);

        self::assertSame(
            \sprintf("\e[%1\$sm%2\$s\e[0m: Oops\n", $sequence, $level->value)
            . "\e[94m  |\e[0m\n"
            . \sprintf("\e[94m1 | \e[0mli\e[%smne\e[0m 1\n", $sequence)
            . \sprintf("\e[94m  | \e[0m  \e[%sm^^\e[0m", $sequence),
            $printer->print(
                [new CapturedSourceLine(1, 0, 'line 1', 3, 2)],
                new ErrorInfo(message: 'Oops', level: $level),
            ),
        );
    }

    #[TestDox('The line delimiters are highlighted along with the source code')]
    public function testHighlightsLineDelimiters(): void
    {
        $printer = new RustStylePrinter(colors: true);

        self::assertSame(
            "\e[94m1 | \e[0mline 1\e[90m␤\e[0m\n"
            . "\e[94m2 | \e[0m\e[90m␤\e[0m\n"
            . "\e[94m3 | \e[0mline 3",
            $printer->print([
                new SourceLine(1, 0, 'line 1'),
                new SourceLine(2, 7, ''),
                new SourceLine(3, 8, 'line 3'),
            ]),
        );
    }

    #[TestDox('The markers of the wrapped line are highlighted')]
    public function testHighlightsWrapMarkers(): void
    {
        $printer = new RustStylePrinter(width: 16, colors: true);

        self::assertSame(
            "\e[94m1 | \e[0maaaaaaaabb\e[90m…\e[0m\n"
            . "\e[94m  | \e[0m\e[90m…\e[0mbbbb",
            $printer->print([new SourceLine(1, 0, \str_repeat('a', 8) . \str_repeat('b', 6))]),
        );
    }

    #[TestDox('The line delimiters are not printed until the colors are enabled')]
    public function testDoesNotPrintLineDelimitersByDefault(): void
    {
        $printer = new RustStylePrinter();

        self::assertSame(
            "1 | line 1\n2 |\n3 | line 3",
            $printer->print([
                new SourceLine(1, 0, 'line 1'),
                new SourceLine(2, 7, ''),
                new SourceLine(3, 8, 'line 3'),
            ]),
        );
    }

    /**
     * @return iterable<non-empty-string, array{Level, non-empty-string}>
     */
    public static function levelsDataProvider(): iterable
    {
        yield 'error' => [Level::Error, '31'];
        yield 'warning' => [Level::Warning, '33'];
        yield 'debug' => [Level::Debug, '1'];
    }

    /**
     * @return non-empty-string
     */
    private static function createSource(): string
    {
        return "line 1\nline 2\nline 3\nline 4\nline 5\nline 6\nline 7";
    }
}
