<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\ErrorPrinter;
use Phplrt\Exception\Printer\Level;
use Phplrt\Exception\SnippetReader;
use Phplrt\Exception\Tests\Stub\ParserRuntimeExceptionStub;
use Phplrt\Exception\Tests\Stub\TokenStub;
use Phplrt\Source\FileSource;
use Phplrt\Source\VirtualSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class ErrorPrinterTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string SOURCE = "first line\nsecond line\nthird line";

    /**
     * @var non-empty-string
     */
    private const string PATHNAME = '/app/example.pp2';

    #[TestDox('The fragment of the source code the error occurred in is printed')]
    public function testPrintsFragmentOfTheError(): void
    {
        self::assertSame(<<<'OUT'
             --> /app/example.pp2:2:8
              |
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT, (string) new ErrorPrinter()->print(self::createError(message: '')));
    }

    #[TestDox('The message and the class of the error are taken from the error itself')]
    public function testPrintsMessageAndClassOfTheError(): void
    {
        self::assertSame(<<<'OUT'
            error[ParserRuntimeExceptionStub]: Something went wrong
             --> /app/example.pp2:2:8
              |
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT, (string) new ErrorPrinter()->print(self::createError()));
    }

    #[TestDox('An error that tells no message is printed without one')]
    public function testPrintsErrorWithoutMessage(): void
    {
        self::assertStringStartsNotWith(
            'error',
            (string) new ErrorPrinter()->print(self::createError(message: '')),
        );
    }

    #[TestDox('The source code of a virtual file is read from the source rather than from the file it is named after')]
    public function testPrintsContentOfAVirtualFileNamedAfterARealOne(): void
    {
        $pathname = self::createSourceFile("another\ncontent\n");

        try {
            $actual = (string) new ErrorPrinter()->print(self::createError(
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
        $actual = (string) new ErrorPrinter()->print(self::createError())
            ->withMessage('Another message');

        self::assertStringStartsWith('error[ParserRuntimeExceptionStub]: Another message', $actual);
    }

    #[TestDox('The error may be printed without any message at all')]
    public function testPrintsWithoutTheMessage(): void
    {
        $actual = (string) new ErrorPrinter()->print(self::createError())
            ->withMessage('');

        self::assertStringStartsNotWith('error', $actual);
    }

    #[TestDox('The message the error tells is printed back after the given one has been taken away')]
    public function testPrintsTheMessageOfTheErrorAgain(): void
    {
        $printer = new ErrorPrinter();
        $error = self::createError();

        self::assertSame(
            (string) $printer->print($error),
            (string) $printer->print($error)->withMessage('Another message')->withMessage(null),
        );
    }

    #[TestDox('The severity of an error telling about it is printed instead of the default one')]
    public function testPrintsSeverityOfTheError(): void
    {
        $actual = (string) new ErrorPrinter()->print(
            new \ErrorException('Something went wrong', severity: \E_USER_WARNING),
        );

        self::assertStringStartsWith('warning[ErrorException]: Something went wrong', $actual);
    }

    #[TestDox('The name of the error may be given instead of the class of it')]
    public function testPrintsGivenClass(): void
    {
        $actual = (string) new ErrorPrinter()->print(self::createError())
            ->withClass('App\Node\SumNodeException');

        self::assertStringStartsWith('error[SumNodeException]: Something went wrong', $actual);
    }

    #[TestDox('The source may be given instead of the one the error occurred in')]
    public function testPrintsGivenSource(): void
    {
        $actual = (string) new ErrorPrinter()->print(self::createError())
            ->withSource(VirtualSource::createFromString('/app/another.pp2', self::SOURCE));

        self::assertStringContainsString('--> /app/another.pp2:2:8', $actual);
    }

    #[TestDox('The fragment may be given instead of the one the error covers')]
    public function testPrintsGivenInterval(): void
    {
        $actual = (string) new ErrorPrinter()->print(self::createError(message: ''))
            ->withInterval(11, 6);

        self::assertStringContainsString("2 | second line\n  | ^^^^^^", $actual);
    }

    #[TestDox('The severity of the error is printed along with its message')]
    public function testPrintsGivenLevel(): void
    {
        $actual = (string) new ErrorPrinter()->print(self::createError())
            ->withLevel(Level::Warning);

        self::assertStringStartsWith('warning[ParserRuntimeExceptionStub]', $actual);
    }

    #[TestDox('The number of lines printed around the fragment is configurable')]
    public function testPrintsGivenLinesCount(): void
    {
        self::assertSame(<<<'OUT'
             --> /app/example.pp2:2:8
              |
            2 | second line
              |        ^^^^
            OUT, (string) new ErrorPrinter()->print(self::createError(message: ''))
            ->withLinesAround(0));
    }

    #[TestDox('The default number of lines around the fragment is used')]
    public function testPrintsDefaultLinesCount(): void
    {
        $printer = new ErrorPrinter();
        $error = self::createError();

        self::assertSame(
            (string) $printer->print($error)->withLinesAround(SnippetReader::DEFAULT_LINES_AROUND),
            (string) $printer->print($error),
        );
    }

    #[TestDox('A negative number of lines around the fragment is reduced to none of them')]
    public function testNegativeLinesCountIsReducedToNone(): void
    {
        $printer = new ErrorPrinter();
        $error = self::createError();

        self::assertSame(
            (string) $printer->print($error)->withLinesAround(0),
            (string) $printer->print($error)->withLinesAround(-42),
        );
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
        self::assertStringStartsNotWith('error', (string) $result);
        self::assertStringStartsWith('error[ParserRuntimeExceptionStub]: Something went wrong', (string) $described);
    }

    #[TestDox('A source stored in a readable file is read from that file')]
    public function testReadsSourceOfAFile(): void
    {
        $pathname = self::createSourceFile(self::SOURCE);

        try {
            $printer = new ErrorPrinter();

            self::assertSame(
                (string) $printer->print(self::createError(
                    message: '',
                    source: VirtualSource::createFromString($pathname, self::SOURCE),
                )),
                (string) $printer->print(self::createError(
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
        $actual = (string) new ErrorPrinter()->print(self::createError(
            message: '',
            source: VirtualSource::createFromString(__DIR__ . '/non-existent-file.txt', self::SOURCE),
        ));

        self::assertStringContainsString('2 | second line', $actual);
    }

    #[TestDox('An error that refers to no source is printed at the place it has been thrown from')]
    public function testPrintsArbitraryException(): void
    {
        $line = __LINE__ + 1;
        $actual = (string) new ErrorPrinter()->print(new \LogicException('Something went wrong'));

        self::assertStringContainsString('error[LogicException]: Something went wrong', $actual);
        self::assertStringContainsString(\sprintf('--> %s:%d:1', __FILE__, $line), $actual);
    }

    /**
     * Returns an error that has occurred on the "line" word of the second
     * line of the source.
     */
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

    /**
     * @return non-empty-string
     */
    private static function createSourceFile(string $content): string
    {
        $pathname = \tempnam(\sys_get_temp_dir(), 'phplrt-error-');

        if ($pathname === false || \file_put_contents($pathname, $content) === false) {
            self::fail('Unable to create a temporary source file');
        }

        return $pathname;
    }
}
