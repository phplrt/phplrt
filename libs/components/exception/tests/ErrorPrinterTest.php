<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Exception\ErrorInfoResult;
use Phplrt\Exception\ErrorPrinter;
use Phplrt\Exception\Printer\ErrorInfo;
use Phplrt\Exception\Printer\Level;
use Phplrt\Source\File;
use Phplrt\Source\Source;
use Phplrt\Source\VirtualFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class ErrorPrinterTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string SOURCE = "first line\nsecond line\nthird line";

    #[TestDox('The fragment of the source code is printed around the given offset')]
    public function testPrintsFragment(): void
    {
        self::assertSame(<<<'OUT'
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT, (string) new ErrorPrinter()->print(new Source(self::SOURCE), 18, 4));
    }

    #[TestDox('The size told after the fragment has been printed describes it the same way')]
    public function testLengthDescribesTheFragment(): void
    {
        $printer = new ErrorPrinter();
        $source = new Source(self::SOURCE);

        self::assertSame(
            (string) $printer->print($source, 18, 4),
            (string) $printer->print($source, 18)->withLength(4),
        );
    }

    #[TestDox('A fragment of a negative size is empty')]
    public function testNegativeLengthIsEmpty(): void
    {
        $printer = new ErrorPrinter();
        $source = new Source(self::SOURCE);

        self::assertSame(
            (string) $printer->print($source, 18),
            (string) $printer->print($source, 18)->withLength(-1),
        );
    }

    #[TestDox('The error is printed above the fragment it occurred in')]
    public function testPrintsErrorInfo(): void
    {
        self::assertSame(<<<'OUT'
            error[SumNodeException]: Something went wrong
              |
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT, (string) new ErrorPrinter()
                ->print(new Source(self::SOURCE), 18, 4)
                ->withMessage('Something went wrong')
                ->withClass('App\Node\SumNodeException'));
    }

    #[TestDox('The whole description of the error may be given at once')]
    public function testPrintsGivenErrorInfo(): void
    {
        $printer = new ErrorPrinter();
        $source = new Source(self::SOURCE);

        self::assertSame(
            (string) $printer->print($source, 18, 4)
                ->withMessage('Something went wrong')
                ->withClass('App\Node\SumNodeException'),
            (string) $printer->print($source, 18, 4)
                ->withErrorInfo(new ErrorInfo(
                    message: 'Something went wrong',
                    class: 'App\Node\SumNodeException',
                )),
        );
    }

    #[TestDox('The name of the file the source belongs to is printed along with the position')]
    public function testPrintsPathnameOfTheSource(): void
    {
        self::assertSame(<<<'OUT'
            error: Something went wrong
             --> /app/example.pp2:2:8
              |
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT, (string) new ErrorPrinter()
                ->print(new VirtualFile('/app/example.pp2', self::SOURCE), 18, 4)
                ->withMessage('Something went wrong'));
    }

    #[TestDox('The name of the file may be given instead of the one the source tells')]
    public function testPrintsGivenPathname(): void
    {
        $actual = (string) new ErrorPrinter()
            ->print(new VirtualFile('/app/example.pp2', self::SOURCE), 18, 4)
            ->withMessage('Something went wrong')
            ->withPathname('/app/another.pp2');

        self::assertStringContainsString('--> /app/another.pp2:2:8', $actual);
    }

    #[TestDox('The severity of the error is printed along with its message')]
    public function testPrintsGivenLevel(): void
    {
        $actual = (string) new ErrorPrinter()
            ->print(new Source(self::SOURCE), 18, 4)
            ->withMessage('Something went wrong')
            ->withLevel(Level::Warning);

        self::assertStringStartsWith('warning: Something went wrong', $actual);
    }

    #[TestDox('The number of lines printed around the fragment is configurable')]
    public function testPrintsGivenLinesCount(): void
    {
        self::assertSame(<<<'OUT'
            2 | second line
              |        ^^^^
            OUT, (string) new ErrorPrinter()
                ->print(new Source(self::SOURCE), 18, 4)
                ->withLinesAround(0));
    }

    #[TestDox('The default number of lines around the fragment is used')]
    public function testPrintsDefaultLinesCount(): void
    {
        $printer = new ErrorPrinter();
        $source = new Source(self::SOURCE);

        self::assertSame(
            (string) $printer->print($source, 18, 4)
                ->withLinesAround(ErrorInfoResult::DEFAULT_LINES_AROUND),
            (string) $printer->print($source, 18, 4),
        );
    }

    #[TestDox('The description of the error is not changed, but a new one is returned')]
    public function testDescriptionIsImmutable(): void
    {
        $result = new ErrorPrinter()->print(new Source(self::SOURCE), 18, 4);

        $described = $result->withMessage('Something went wrong');

        self::assertNotSame($result, $described);
        self::assertStringStartsNotWith('error', (string) $result);
        self::assertStringStartsWith('error: Something went wrong', (string) $described);
    }

    #[TestDox('A source stored in a readable file is read from that file')]
    public function testReadsSourceOfAFile(): void
    {
        $pathname = self::createSourceFile(self::SOURCE);

        try {
            self::assertSame(
                (string) new ErrorPrinter()->print(new Source(self::SOURCE), 18, 4),
                (string) new ErrorPrinter()->print(new File($pathname), 18, 4)
                    ->withPathname(null),
            );
        } finally {
            @\unlink($pathname);
        }
    }

    #[TestDox('A source of a file that cannot be read is read as the source code it holds')]
    public function testReadsSourceOfAVirtualFile(): void
    {
        self::assertSame(
            (string) new ErrorPrinter()->print(new Source(self::SOURCE), 18, 4),
            (string) new ErrorPrinter()
                ->print(new VirtualFile(__DIR__ . '/non-existent-file.txt', self::SOURCE), 18, 4)
                ->withPathname(null),
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
