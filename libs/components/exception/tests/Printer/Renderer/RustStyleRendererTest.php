<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Printer\Renderer;

use Phplrt\Exception\Analysis\FailureInterval;
use Phplrt\Exception\Analysis\FailureLevel;
use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Exception\Printer\Renderer\AnsiRustStyleRenderer;
use Phplrt\Exception\Printer\Renderer\RawRustStyleRenderer;
use Phplrt\Exception\Printer\Renderer\RendererInterface;
use Phplrt\Exception\Printer\Renderer\RustStyleRenderer;
use Phplrt\Exception\Tests\TestCase;
use Phplrt\Position\Position;
use Phplrt\Position\PositionFactory;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/exception')]
#[Test]
final class RustStyleRendererTest extends TestCase
{
    private const string SOURCE = "line 1\nline 2\nline 3\nline 4\nline 5\nline 6\nline 7";

    public function testPrintsLinesWithTheUnderlinedFragment(): void
    {
        Assert::same(self::render(self::createFailure(self::SOURCE, 23, 2)), <<<'OUT'
            error
            2 | line 2
            3 | line 3
            4 | line 4
              |   ^^
            5 | line 5
            6 | line 6
            OUT);
    }

    public function testAlignsLineNumbers(): void
    {
        $source = \implode("\n", \array_map(static fn(int $i): string => 'line ' . $i, \range(1, 12)));

        Assert::same(self::render(self::createFailure($source, 63, 7)), <<<'OUT'
            error
             8 | line 8
             9 | line 9
            10 | line 10
               | ^^^^^^^
            11 | line 11
            12 | line 12
            OUT);
    }

    public function testUnderlinesCharactersInsteadOfBytes(): void
    {
        $value = 'Hello Вася';
        $offset = \strpos($value, 'Вася');

        Assert::int($offset);

        Assert::same(self::render(self::createFailure($value, $offset, \strlen('Вася'))), <<<'OUT'
            error
            1 | Hello Вася
              |       ^^^^
            OUT);
    }

    public function testUnderlinesEmptyFragment(): void
    {
        Assert::same(self::render(self::createFailure('line 1', 3, 0)), <<<'OUT'
            error
            1 | line 1
              |    ^
            OUT);
    }

    public function testUnderlinesEveryLineOfTheFragment(): void
    {
        Assert::same(self::render(self::createFailure(self::SOURCE, 26, 13)), <<<'OUT'
            error
            2 | line 2
            3 | line 3
            4 | line 4
              |      ^
            5 | line 5
              | ^^^^^^
            6 | line 6
              | ^^^^
            7 | line 7
            OUT);
    }

    public function testPrintsLineOfAnyLength(): void
    {
        $value = \str_repeat('a', 200) . 'ERROR';

        Assert::same(self::render(self::createFailure($value, 200, 5)), "error\n"
        . '1 | ' . $value . "\n"
        . '  | ' . \str_repeat(' ', 200) . '^^^^^');
    }

    public function testPrintsEmptyLine(): void
    {
        Assert::same(self::render(self::createFailure("line 1\n\nline 3", 3, 12)), <<<'OUT'
            error
            1 | line 1
              |    ^^^
            2 |
            3 | line 3
              | ^^^^^^
            OUT);
    }

    public function testPrintsErrorMessage(): void
    {
        Assert::same(self::render(self::createFailure(self::SOURCE, 23, 2, message: 'Unexpected token')), <<<'OUT'
            error: Unexpected token
            2 | line 2
            3 | line 3
            4 | line 4
              |   ^^
            5 | line 5
            6 | line 6
            OUT);
    }

    public function testPrintsErrorLocation(): void
    {
        Assert::same(self::render(self::createFailure(
            self::SOURCE,
            23,
            2,
            message: 'Unexpected token',
            class: \LogicException::class,
            pathname: '/app/example.php',
        )), <<<'OUT'
            error[LogicException]: Unexpected token
             --> /app/example.php:4:3
            2 | line 2
            3 | line 3
            4 | line 4
              |   ^^
            5 | line 5
            6 | line 6
            OUT);
    }

    public function testPrintsErrorLocationColumnInCharacters(): void
    {
        $value = 'Привет Вася';
        $offset = \strpos($value, 'Вася');

        Assert::int($offset);

        Assert::same(self::render(self::createFailure(
            $value,
            $offset,
            \strlen('Вася'),
            pathname: '/app/example.php',
        )), <<<'OUT'
            error
             --> /app/example.php:1:8
            1 | Привет Вася
              |        ^^^^
            OUT);
    }

    public function testPrintsShortClassName(): void
    {
        Assert::same(self::render(self::createFailure(
            self::SOURCE,
            21,
            4,
            message: 'Something went wrong',
            class: 'Phplrt\Parser\Exception\UnexpectedTokenException',
        )), <<<'OUT'
            error[UnexpectedTokenException]: Something went wrong
            2 | line 2
            3 | line 3
            4 | line 4
              | ^^^^
            5 | line 5
            6 | line 6
            OUT);
    }

    public function testPrintsErrorLevel(): void
    {
        Assert::same(self::render(self::createFailure(
            self::SOURCE,
            21,
            4,
            message: 'Unused variable',
            level: FailureLevel::Warning,
        )), <<<'OUT'
            warning: Unused variable
            2 | line 2
            3 | line 3
            4 | line 4
              | ^^^^
            5 | line 5
            6 | line 6
            OUT);
    }

    public function testPrintsErrorWithoutFragment(): void
    {
        Assert::same(self::render(self::createFailure(self::SOURCE, message: 'Unexpected end of input')), <<<'OUT'
            error: Unexpected end of input
            1 | line 1
              | ^^^^^^
            2 | line 2
            3 | line 3
            OUT);
    }

    public function testPrintsStackTrace(): void
    {
        $e = new \LogicException();

        Assert::true(\str_ends_with(new RawRustStyleRenderer()->render(self::createFailure(self::SOURCE, 23, 2), $e), "\n" . $e->getTraceAsString()));
    }

    public function testRawRendererPrintsNoEscapeSequences(): void
    {
        Assert::string(self::render(
            self::createFailure('line 1', 2, 2, message: 'Oops'),
        ))->notContains("\e");
    }

    #[DataProvider('levelsDataProvider')]
    public function testHighlightsError(FailureLevel $level, string $sequence): void
    {
        Assert::same(self::render(
            self::createFailure('line 1', 2, 2, message: 'Oops', level: $level),
            new AnsiRustStyleRenderer(),
        ), \sprintf("\e[%1\$sm%2\$s\e[0m: Oops\n", $sequence, $level->value)
        . \sprintf("\e[94m1 | \e[0mli\e[%smne\e[0m 1\n", $sequence)
        . \sprintf("\e[94m  | \e[0m  \e[%sm^^\e[0m", $sequence));
    }

    public static function levelsDataProvider(): iterable
    {
        yield 'error' => [FailureLevel::Error, '31'];
        yield 'warning' => [FailureLevel::Warning, '33'];
        yield 'debug' => [FailureLevel::Debug, '1'];
    }

    public function testHighlightsLineDelimiters(): void
    {
        Assert::same(self::render(
            self::createFailure("line 1\n\nline 3", 0, 1),
            new AnsiRustStyleRenderer(),
        ), "\e[31merror\e[0m\n"
        . "\e[94m1 | \e[0m\e[31ml\e[0mine 1\e[90m␤\e[0m\n"
        . "\e[94m  | \e[0m\e[31m^\e[0m\n"
        . "\e[94m2 | \e[0m\e[90m␤\e[0m\n"
        . "\e[94m3 | \e[0mline 3");
    }

    public function testDefaultRendererOfThePlainOutput(): void
    {
        Assert::instanceOf(RustStyleRenderer::createDefault(), RawRustStyleRenderer::class);
    }

    public function testDefaultRendererOfTheColoredOutput(): void
    {
        self::withEnv(['NO_COLOR' => null, 'FORCE_COLOR' => '1'], static function (): void {
            Assert::instanceOf(RustStyleRenderer::createDefault(), AnsiRustStyleRenderer::class);
        });
    }

    private static function render(FailureResult $error, ?RendererInterface $renderer = null): string
    {
        $e = new \LogicException();

        $result = ($renderer ?? new RawRustStyleRenderer())->render($error, $e);

        return \rtrim(\substr($result, 0, -\strlen($e->getTraceAsString())), "\n");
    }

    private static function createFailure(
        string $code,
        ?int $offset = null,
        int $length = 0,
        string $message = '',
        string $class = '',
        ?string $pathname = null,
        FailureLevel $level = FailureLevel::Error,
    ): FailureResult {
        $source = $pathname === null
            ? StringSource::createFromString($code)
            : VirtualSource::createFromString($pathname, $code);

        return new FailureResult(
            class: $class,
            message: $message,
            source: $source,
            position: $offset === null
                ? new Position()
                : new PositionFactory()->createFromOffset($source, $offset),
            level: $level,
            interval: $offset === null ? null : new FailureInterval($offset, $length),
        );
    }

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
