<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Analysis\FailureLevel;
use Phplrt\Exception\ErrorPrinter;
use Phplrt\Exception\Tests\Stub\ParserRuntimeExceptionStub;
use Phplrt\Exception\Tests\Stub\TokenStub;
use Phplrt\Source\FileSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/exception')]
#[Test]
final class ErrorPrinterTest extends TestCase
{
    private const string SOURCE = "first line\nsecond line\nthird line";

    private const string PATHNAME = '/app/example.pp2';

    public function testPrintsMessageAndClassOfTheError(): void
    {
        Assert::same(self::print(self::createError()), <<<'OUT'
            error[ParserRuntimeExceptionStub]: Something went wrong
             --> /app/example.pp2:2:8
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT);
    }

    public function testPrintsErrorWithoutMessage(): void
    {
        Assert::true(\str_starts_with(self::print(self::createError(message: '')), "error[ParserRuntimeExceptionStub]\n"));
    }

    public function testPrintsContentOfAVirtualFileNamedAfterARealOne(): void
    {
        $pathname = self::createSourceFile("another\ncontent\n");

        try {
            $actual = self::print(self::createError(
                source: VirtualSource::createFromString($pathname, self::SOURCE),
            ));

            Assert::string($actual)
                ->contains('second line')
                ->notContains('content');
        } finally {
            @\unlink($pathname);
        }
    }

    public function testPrintsGivenMessage(): void
    {
        $actual = self::print(self::createError(), static fn($printed) => $printed
            ->withMessage('Another message'));

        Assert::true(\str_starts_with($actual, 'error[ParserRuntimeExceptionStub]: Another message'));
    }

    public function testPrintsGivenClass(): void
    {
        $actual = self::print(self::createError(), static fn($printed) => $printed
            ->withClass('App\Node\SumNodeException'));

        Assert::true(\str_starts_with($actual, 'error[SumNodeException]: Something went wrong'));
    }

    public function testPrintsWithoutTheClass(): void
    {
        $actual = self::print(self::createError(), static fn($printed) => $printed->withClass(''));

        Assert::true(\str_starts_with($actual, 'error: Something went wrong'));
    }

    public function testPrintsGivenSource(): void
    {
        $actual = self::print(self::createError(), static fn($printed) => $printed
            ->withSource(VirtualSource::createFromString('/app/another.pp2', self::SOURCE)));

        Assert::string($actual)->contains('--> /app/another.pp2:2:8');
    }

    public function testPrintsGivenInterval(): void
    {
        $actual = self::print(self::createError(message: ''), static fn($printed) => $printed
            ->withInterval(11, 6));

        Assert::string($actual)->contains("2 | second line\n  | ^^^^^^");
    }

    public function testPrintsWithoutTheInterval(): void
    {
        $actual = self::print(self::createError(message: ''), static fn($printed) => $printed
            ->withoutInterval());

        Assert::string($actual)->contains("2 | second line\n  |        ^");
    }

    public function testPrintsGivenLevel(): void
    {
        $actual = self::print(self::createError(), static fn($printed) => $printed
            ->withLevel(FailureLevel::Warning));

        Assert::true(\str_starts_with($actual, 'warning[ParserRuntimeExceptionStub]'));
    }

    public function testPrintsSeverityOfTheError(): void
    {
        $actual = self::print(new \ErrorException('Something went wrong', severity: \E_USER_WARNING));

        Assert::true(\str_starts_with($actual, 'warning[ErrorException]: Something went wrong'));
    }

    public function testPrintsStackTrace(): void
    {
        $e = self::createError();

        Assert::true(\str_ends_with((string) new ErrorPrinter()->print($e), "\n" . $e->getTraceAsString()));
    }

    public function testPrintsEveryErrorOfTheChain(): void
    {
        $actual = self::print(new \LogicException('Compilation failed', 0, self::createError()));

        Assert::string($actual)
            ->contains('error[LogicException]: Compilation failed')
            ->contains('error[ParserRuntimeExceptionStub]: Something went wrong')
            ->contains('--> /app/example.pp2:2:8');
    }

    public function testRenderIsTheSameAsTheStringConversion(): void
    {
        $result = new ErrorPrinter()->print(self::createError());

        Assert::same((string) $result, $result->render());
    }

    public function testDescriptionIsImmutable(): void
    {
        $result = new ErrorPrinter()->print(self::createError(message: ''));

        $described = $result->withMessage('Something went wrong');

        Assert::notSame($described, $result);
        Assert::true(\str_starts_with((string) $result, "error[ParserRuntimeExceptionStub]\n"));
        Assert::true(\str_starts_with((string) $described, 'error[ParserRuntimeExceptionStub]: Something went wrong'));
    }

    public function testReadsSourceOfAFile(): void
    {
        $pathname = self::createSourceFile(self::SOURCE);

        try {
            Assert::same(self::print(self::createError(
                message: '',
                source: FileSource::createFromPathname($pathname),
            )), self::print(self::createError(
                message: '',
                source: VirtualSource::createFromString($pathname, self::SOURCE),
            )));
        } finally {
            @\unlink($pathname);
        }
    }

    public function testReadsSourceOfAVirtualFile(): void
    {
        $actual = self::print(self::createError(
            message: '',
            source: VirtualSource::createFromString(__DIR__ . '/non-existent-file.txt', self::SOURCE),
        ));

        Assert::string($actual)->contains('2 | second line');
    }

    public function testPrintsArbitraryException(): void
    {
        $line = __LINE__ + 1;
        $actual = self::print(new \LogicException('Something went wrong'));

        Assert::string($actual)
            ->contains('error[LogicException]: Something went wrong')
            ->contains(\sprintf('--> %s:%d:1', __FILE__, $line));
    }

    private static function print(\Throwable $e, ?\Closure $then = null): string
    {
        $printed = new ErrorPrinter()->print($e);

        $result = (string) ($then === null ? $printed : $then($printed));

        return \rtrim(\substr($result, 0, -\strlen($e->getTraceAsString())), "\n");
    }

    private static function createError(
        string $message = 'Something went wrong',
        ?ReadableInterface $source = null,
    ): ParserRuntimeExceptionStub {
        return new ParserRuntimeExceptionStub(
            source: $source ?? VirtualSource::createFromString(self::PATHNAME, self::SOURCE),
            token: new TokenStub(offset: 18, value: 'line'),
            message: $message,
        );
    }

    private static function createSourceFile(string $content): string
    {
        $pathname = \tempnam(\sys_get_temp_dir(), 'phplrt-error-');

        if ($pathname === false || \file_put_contents($pathname, $content) === false) {
            Assert::fail('Unable to create a temporary source file');
        }

        return $pathname;
    }
}
