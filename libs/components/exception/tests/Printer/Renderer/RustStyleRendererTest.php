<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Printer\Renderer;

use Phplrt\Exception\Analysis\AnalyzedExceptionResult;
use Phplrt\Exception\Analysis\FailureInterval;
use Phplrt\Exception\Printer\PrintableError;
use Phplrt\Exception\Printer\Level;
use Phplrt\Exception\Printer\Renderer\AnsiRustStyleRenderer;
use Phplrt\Exception\Printer\Renderer\RawRustStyleRenderer;
use Phplrt\Exception\Printer\Renderer\RustStyleRenderer;
use Phplrt\Exception\Snippet\CapturedSourceLine;
use Phplrt\Exception\Snippet\SourceLine;
use Phplrt\Exception\SnippetReader;
use Phplrt\Exception\Tests\Stub\FilelessExceptionStub;
use Phplrt\Exception\Tests\TestCase;
use Phplrt\Position\Position;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class RustStyleRendererTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string SOURCE = "line 1\nline 2\nline 3\nline 4\nline 5\nline 6\nline 7";

    #[TestDox('Every line is prefixed by its number and the captured one is underlined')]
    public function testPrintsLinesWithTheUnderlinedFragment(): void
    {
        self::assertSame(<<<'OUT'
            2 | line 2
            3 | line 3
            4 | line 4
              |   ^^
            5 | line 5
            6 | line 6
            OUT, self::render(self::read(23, 2, 2)));
    }

    #[TestDox('The line numbers are aligned to the widest one')]
    public function testAlignsLineNumbers(): void
    {
        $source = \implode("\n", \array_map(static fn(int $i): string => 'line ' . $i, \range(1, 12)));

        self::assertSame(<<<'OUT'
             8 | line 8
             9 | line 9
            10 | line 10
               | ^^^^^^^
            11 | line 11
            12 | line 12
            OUT, self::render(self::read(63, 7, 2, $source)));
    }

    #[TestDox('The multi-byte characters are underlined as single ones')]
    public function testUnderlinesCharactersInsteadOfBytes(): void
    {
        $value = 'Hello Вася';
        $offset = \strpos($value, 'Вася');

        self::assertIsInt($offset);

        self::assertSame(<<<'OUT'
            1 | Hello Вася
              |       ^^^^
            OUT, self::render([new CapturedSourceLine(1, 0, $value, new FailureInterval($offset, \strlen('Вася')))]));
    }

    #[TestDox('An empty fragment is underlined by a single character')]
    public function testUnderlinesEmptyFragment(): void
    {
        self::assertSame(<<<'OUT'
            1 | line 1
              |    ^
            OUT, self::render([new CapturedSourceLine(1, 0, 'line 1', new FailureInterval(3, 0))]));
    }

    #[TestDox('Every line of a multi-line fragment is underlined')]
    public function testUnderlinesEveryLineOfTheFragment(): void
    {
        self::assertSame(<<<'OUT'
            3 | line 3
            4 | line 4
              |      ^
            5 | line 5
              | ^^^^^^
            6 | line 6
              | ^^^^
            7 | line 7
            OUT, self::render(self::read(26, 13, 1)));
    }

    #[TestDox('A line is printed as long as it is')]
    public function testPrintsLineOfAnyLength(): void
    {
        $value = \str_repeat('a', 200) . 'ERROR';

        self::assertSame(
            '1 | ' . $value . "\n"
            . '  | ' . \str_repeat(' ', 200) . '^^^^^',
            self::render([new CapturedSourceLine(1, 0, $value, new FailureInterval(200, 5))]),
        );
    }

    #[TestDox('The lines without a captured fragment are printed as is')]
    public function testPrintsLinesWithoutTheCapturedFragment(): void
    {
        self::assertSame(<<<'OUT'
            1 | line 1
            2 | line 2
            OUT, self::render([new SourceLine(1, 0, 'line 1'), new SourceLine(2, 7, 'line 2')]));
    }

    #[TestDox('The empty line is printed without the trailing whitespaces')]
    public function testPrintsEmptyLine(): void
    {
        self::assertSame("1 |\n2 | line 2", self::render([
            new SourceLine(1, 0, ''),
            new SourceLine(2, 1, 'line 2'),
        ]));
    }

    #[TestDox('The empty list of lines is printed as an empty string')]
    public function testPrintsEmptyList(): void
    {
        self::assertSame('', self::render([]));
    }

    #[TestDox('The line without characters of the fragment is not underlined')]
    public function testDoesNotUnderlineLineWithoutCharactersOfTheFragment(): void
    {
        self::assertSame(<<<'OUT'
            1 | line 1
              |    ^^^
            2 |
            3 | line 3
              | ^^^^^^
            OUT, self::render(self::read(3, 12, 0, "line 1\n\nline 3")));
    }

    #[TestDox('The error message is printed above the source code')]
    public function testPrintsErrorMessage(): void
    {
        self::assertSame(<<<'OUT'
            error: Unexpected token
              |
            1 | line 1
              |    ^^^
            OUT, self::render(
            [new CapturedSourceLine(1, 0, 'line 1', new FailureInterval(3, 3))],
            self::createPrintable(message: 'Unexpected token'),
        ));
    }

    #[TestDox('The error location is printed above the source code')]
    public function testPrintsErrorLocation(): void
    {
        self::assertSame(<<<'OUT'
            error[LogicException]: Unexpected token
              --> /app/example.php:42:4
               |
            42 | line 1
               |    ^^^
            OUT, self::render(
            [new CapturedSourceLine(42, 0, 'line 1', new FailureInterval(3, 3))],
            self::createPrintable(
                message: 'Unexpected token',
                pathname: '/app/example.php',
                class: \LogicException::class,
            ),
        ));
    }

    #[TestDox('The column of the error location is counted in characters')]
    public function testPrintsErrorLocationColumnInCharacters(): void
    {
        $value = 'Привет Вася';
        $offset = \strpos($value, 'Вася');

        self::assertIsInt($offset);

        self::assertSame(<<<'OUT'
             --> /app/example.php:1:8
              |
            1 | Привет Вася
              |        ^^^^
            OUT, self::render(
            [new CapturedSourceLine(1, 0, $value, new FailureInterval($offset, \strlen('Вася')))],
            self::createPrintable(pathname: '/app/example.php'),
        ));
    }

    #[TestDox('The class the error is named by is printed without the namespace it belongs to')]
    public function testPrintsShortClassName(): void
    {
        self::assertSame(<<<'OUT'
            error[UnexpectedTokenException]: Something went wrong
              |
            1 | line 1
              | ^^^^
            OUT, self::render(
            [new CapturedSourceLine(1, 0, 'line 1', new FailureInterval(0, 4))],
            self::createPrintable(
                message: 'Something went wrong',
                class: 'Phplrt\Parser\Exception\UnexpectedTokenException',
            ),
        ));
    }

    #[TestDox('The error information is printed without the source code')]
    public function testPrintsErrorInformationWithoutSourceCode(): void
    {
        self::assertSame(
            'error: Unexpected end of input',
            self::render([], self::createPrintable(message: 'Unexpected end of input')),
        );
    }

    #[TestDox('The severity of the error is printed instead of the default one')]
    public function testPrintsErrorLevel(): void
    {
        self::assertSame(<<<'OUT'
            warning: Unused variable
              |
            1 | line 1
              | ^^^^
            OUT, self::render(
            [new CapturedSourceLine(1, 0, 'line 1', new FailureInterval(0, 4))],
            self::createPrintable(message: 'Unused variable', level: Level::Warning),
        ));
    }

    #[TestDox('The plain renderer prints no escape sequences')]
    public function testRawRendererPrintsNoEscapeSequences(): void
    {
        self::assertStringNotContainsString("\e", self::renderExample(new RawRustStyleRenderer()));
    }

    #[TestDox('The ANSI renderer prints the escape sequences')]
    public function testAnsiRendererPrintsEscapeSequences(): void
    {
        self::assertStringContainsString("\e", self::renderExample(new AnsiRustStyleRenderer()));
    }

    /**
     * @param non-empty-string $sequence
     */
    #[TestDox('The severity, the captured fragment and its underline are highlighted')]
    #[DataProvider('levelsDataProvider')]
    public function testHighlightsError(Level $level, string $sequence): void
    {
        self::assertSame(
            \sprintf("\e[%1\$sm%2\$s\e[0m: Oops\n", $sequence, $level->value)
            . "\e[94m  |\e[0m\n"
            . \sprintf("\e[94m1 | \e[0mli\e[%smne\e[0m 1\n", $sequence)
            . \sprintf("\e[94m  | \e[0m  \e[%sm^^\e[0m", $sequence),
            new AnsiRustStyleRenderer()->render(
                [new CapturedSourceLine(1, 0, 'line 1', new FailureInterval(2, 2))],
                self::createPrintable(message: 'Oops', level: $level),
            ),
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

    #[TestDox('The line delimiters are highlighted along with the source code')]
    public function testHighlightsLineDelimiters(): void
    {
        self::assertSame(
            "\e[94m1 | \e[0mline 1\e[90m␤\e[0m\n"
            . "\e[94m2 | \e[0m\e[90m␤\e[0m\n"
            . "\e[94m3 | \e[0mline 3",
            new AnsiRustStyleRenderer()->render([
                new SourceLine(1, 0, 'line 1'),
                new SourceLine(2, 7, ''),
                new SourceLine(3, 8, 'line 3'),
            ], self::createPrintable()),
        );
    }

    #[TestDox('The output asked to stay plain is rendered as a plain text')]
    public function testDefaultRendererOfThePlainOutput(): void
    {
        // The "NO_COLOR" variable is set by the configuration of the tests
        self::assertInstanceOf(RawRustStyleRenderer::class, RustStyleRenderer::createDefault());
    }

    #[TestDox('The output asked to print the colors is rendered with the escape sequences')]
    public function testDefaultRendererOfTheColoredOutput(): void
    {
        self::withEnv(['NO_COLOR' => null, 'FORCE_COLOR' => '1'], static function (): void {
            self::assertInstanceOf(AnsiRustStyleRenderer::class, RustStyleRenderer::createDefault());
        });
    }

    /**
     * @param iterable<mixed, SourceLine> $lines
     */
    private static function render(iterable $lines, ?PrintableError $error = null): string
    {
        return new RawRustStyleRenderer()->render($lines, $error ?? self::createPrintable());
    }

    private static function renderExample(RustStyleRenderer $renderer): string
    {
        return $renderer->render(
            [new CapturedSourceLine(1, 0, 'line 1', new FailureInterval(2, 2))],
            self::createPrintable(message: 'Oops'),
        );
    }

    /**
     * Returns the error the given information is printed for, the analysis of
     * which is of no interest to the renderer itself.
     */
    private static function createPrintable(
        string $message = '',
        ?string $pathname = null,
        string $class = '',
        Level $level = Level::Error,
    ): PrintableError {
        return new PrintableError(
            reader: new SnippetReader(),
            renderer: new RawRustStyleRenderer(),
            // The error belongs to no file of its own, so the pathname is
            // printed only in case the source it occurred in is named
            error: new AnalyzedExceptionResult(
                exception: new FilelessExceptionStub(),
                source: $pathname === null
                    ? StringSource::createEmpty()
                    : VirtualSource::createFromString($pathname, ''),
                position: new Position(),
            ),
            message: $message,
            class: $class,
            level: $level,
        );
    }

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @param int<0, max> $lines
     * @return array<int<1, max>, SourceLine>
     */
    private static function read(int $offset, int $length, int $lines, string $code = self::SOURCE): array
    {
        return new SnippetReader()
            ->fragment(new StringSource($code), new FailureInterval($offset, $length), $lines);
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
}
