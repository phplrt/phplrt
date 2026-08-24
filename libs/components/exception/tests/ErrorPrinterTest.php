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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class ErrorPrinterTest extends TestCase
{
    private const string SOURCE = "first line\nsecond line\nthird line";

    private const string PATHNAME = '/app/example.pp2';

    #[TestDox('The message and the class of the error are taken from the error itself')]
    public function testPrintsMessageAndClassOfTheError(): void
    {
        self::assertSame(<<<'OUT'
            error[ParserRuntimeExceptionStub]: Something went wrong
             --> /app/example.pp2:2:8
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT, self::print(self::createError()));
    }

    #[TestDox('An error that tells no message is printed under its severity and name alone')]
    public function testPrintsErrorWithoutMessage(): void
    {
        self::assertStringStartsWith(
            "error[ParserRuntimeExceptionStub]\n",
            self::print(self::createError(message: '')),
        );
    }

    #[TestDox('The source code of a virtual file is read from the source rather than from the file it is named after')]
    public function testPrintsContentOfAVirtualFileNamedAfterARealOne(): void
    {
        $pathname = self::createSourceFile("another\ncontent\n");

        try {
            $actual = self::print(self::createError(
                source: VirtualSource::createFromString($pathname, self::SOURCE),
            ));

            self::assertStringContainsString('second line', $actual);
            self::assertStringNotContainsString('content', $actual);
        } finally {
            @\unlink($pathname);
        }
    }

    #[TestDox('The message may be given instead of the one the error tells')]
    public function testPrintsGivenMessage(): void
    {
        $actual = self::print(self::createError(), static fn($printed) => $printed
            ->withMessage('Another message'));

        self::assertStringStartsWith('error[ParserRuntimeExceptionStub]: Another message', $actual);
    }

    #[TestDox('The name of the error may be given instead of the class of it')]
    public function testPrintsGivenClass(): void
    {
        $actual = self::print(self::createError(), static fn($printed) => $printed
            ->withClass('App\Node\SumNodeException'));

        self::assertStringStartsWith('error[SumNodeException]: Something went wrong', $actual);
    }

    #[TestDox('The error may be printed under the severity alone')]
    public function testPrintsWithoutTheClass(): void
    {
        $actual = self::print(self::createError(), static fn($printed) => $printed->withClass(''));

        self::assertStringStartsWith('error: Something went wrong', $actual);
    }

    #[TestDox('The source may be given instead of the one the error occurred in')]
    public function testPrintsGivenSource(): void
    {
        $actual = self::print(self::createError(), static fn($printed) => $printed
            ->withSource(VirtualSource::createFromString('/app/another.pp2', self::SOURCE)));

        self::assertStringContainsString('--> /app/another.pp2:2:8', $actual);
    }

    #[TestDox('The fragment may be given instead of the one the error covers')]
    public function testPrintsGivenInterval(): void
    {
        $actual = self::print(self::createError(message: ''), static fn($printed) => $printed
            ->withInterval(11, 6));

        self::assertStringContainsString("2 | second line\n  | ^^^^^^", $actual);
    }

    #[TestDox('An error covering no fragment points at the place it occurred at')]
    public function testPrintsWithoutTheInterval(): void
    {
        $actual = self::print(self::createError(message: ''), static fn($printed) => $printed
            ->withoutInterval());

        self::assertStringContainsString("2 | second line\n  |        ^", $actual);
    }

    #[TestDox('The severity of the error is printed along with its message')]
    public function testPrintsGivenLevel(): void
    {
        $actual = self::print(self::createError(), static fn($printed) => $printed
            ->withLevel(FailureLevel::Warning));

        self::assertStringStartsWith('warning[ParserRuntimeExceptionStub]', $actual);
    }

    #[TestDox('The severity of an error telling about it is printed instead of the default one')]
    public function testPrintsSeverityOfTheError(): void
    {
        $actual = self::print(new \ErrorException('Something went wrong', severity: \E_USER_WARNING));

        self::assertStringStartsWith('warning[ErrorException]: Something went wrong', $actual);
    }

    #[TestDox('The stack trace of the error closes the output')]
    public function testPrintsStackTrace(): void
    {
        $e = self::createError();

        self::assertStringEndsWith(
            "\n" . $e->getTraceAsString(),
            (string) new ErrorPrinter()->print($e),
        );
    }

    #[TestDox('Every error of the chain is printed, from the outermost one to the innermost')]
    public function testPrintsEveryErrorOfTheChain(): void
    {
        $actual = self::print(new \LogicException('Compilation failed', 0, self::createError()));

        self::assertStringContainsString('error[LogicException]: Compilation failed', $actual);
        self::assertStringContainsString('error[ParserRuntimeExceptionStub]: Something went wrong', $actual);
        self::assertStringContainsString('--> /app/example.pp2:2:8', $actual);
    }

    #[TestDox('The rendered error is the same as the one the object is converted to')]
    public function testRenderIsTheSameAsTheStringConversion(): void
    {
        $result = new ErrorPrinter()->print(self::createError());

        self::assertSame($result->render(), (string) $result);
    }

    #[TestDox('The description of the error is not changed, but a new one is returned')]
    public function testDescriptionIsImmutable(): void
    {
        $result = new ErrorPrinter()->print(self::createError(message: ''));

        $described = $result->withMessage('Something went wrong');

        self::assertNotSame($result, $described);
        self::assertStringStartsWith("error[ParserRuntimeExceptionStub]\n", (string) $result);
        self::assertStringStartsWith('error[ParserRuntimeExceptionStub]: Something went wrong', (string) $described);
    }

    #[TestDox('A source stored in a readable file is read from that file')]
    public function testReadsSourceOfAFile(): void
    {
        $pathname = self::createSourceFile(self::SOURCE);

        try {
            self::assertSame(
                self::print(self::createError(
                    message: '',
                    source: VirtualSource::createFromString($pathname, self::SOURCE),
                )),
                self::print(self::createError(
                    message: '',
                    source: FileSource::createFromPathname($pathname),
                )),
            );
        } finally {
            @\unlink($pathname);
        }
    }

    #[TestDox('A source of a file that cannot be read is read as the source code it holds')]
    public function testReadsSourceOfAVirtualFile(): void
    {
        $actual = self::print(self::createError(
            message: '',
            source: VirtualSource::createFromString(__DIR__ . '/non-existent-file.txt', self::SOURCE),
        ));

        self::assertStringContainsString('2 | second line', $actual);
    }

    #[TestDox('An error that refers to no source is printed at the place it has been thrown from')]
    public function testPrintsArbitraryException(): void
    {
        $line = __LINE__ + 1;
        $actual = self::print(new \LogicException('Something went wrong'));

        self::assertStringContainsString('error[LogicException]: Something went wrong', $actual);
        self::assertStringContainsString(\sprintf('--> %s:%d:1', __FILE__, $line), $actual);
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
            self::fail('Unable to create a temporary source file');
        }

        return $pathname;
    }
}
