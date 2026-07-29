<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Snippet\Reader\Content;

use Phplrt\Exception\Snippet\Exception\SourceNotReadableException;
use Phplrt\Exception\Snippet\Reader\Content\ContentInterface;
use Phplrt\Exception\Snippet\Reader\Content\FileContent;
use Phplrt\Exception\Snippet\Reader\Content\StringContent;
use Phplrt\Exception\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class ContentTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string SOURCE = "line 1\nline 2\nline 3";

    #[TestDox('The size of the source is the number of its bytes')]
    public function testSize(): void
    {
        self::assertSame(20, (new StringContent(self::SOURCE))->getSize());
        self::assertSame(0, (new StringContent(''))->getSize());
    }

    #[TestDox('The needle is searched backwards from the given offset')]
    public function testFindBefore(): void
    {
        $content = new StringContent(self::SOURCE);

        self::assertNull($content->findBefore("\n", 0));
        self::assertNull($content->findBefore("\n", 6));
        self::assertSame(6, $content->findBefore("\n", 7));
        self::assertSame(6, $content->findBefore("\n", 13));
        self::assertSame(13, $content->findBefore("\n", 14));
        self::assertSame(13, $content->findBefore("\n", 20));
    }

    #[TestDox('The needle is searched forwards from the given offset')]
    public function testFindAfter(): void
    {
        $content = new StringContent(self::SOURCE);

        self::assertSame(6, $content->findAfter("\n", 0));
        self::assertSame(6, $content->findAfter("\n", 6));
        self::assertSame(13, $content->findAfter("\n", 7));
        self::assertSame(13, $content->findAfter("\n", 13));
        self::assertNull($content->findAfter("\n", 14));
        self::assertNull($content->findAfter("\n", 20));
    }

    #[TestDox('The needles located before the given offset are counted')]
    public function testCountBefore(): void
    {
        $content = new StringContent(self::SOURCE);

        self::assertSame(0, $content->countBefore("\n", 0));
        self::assertSame(0, $content->countBefore("\n", 6));
        self::assertSame(1, $content->countBefore("\n", 7));
        self::assertSame(1, $content->countBefore("\n", 13));
        self::assertSame(2, $content->countBefore("\n", 14));
        self::assertSame(2, $content->countBefore("\n", 20));
    }

    #[TestDox('The bytes located at the given offset are read')]
    public function testRead(): void
    {
        $content = new StringContent(self::SOURCE);

        self::assertSame('line 1', $content->read(0, 6));
        self::assertSame('line 3', $content->read(14, 6));
        self::assertSame('', $content->read(0, 0));
        self::assertSame('line 3', $content->read(14, 42));
        self::assertSame('', $content->read(42, 42));
    }

    #[TestDox('A file is read in the same way as a string')]
    public function testFileIsEquivalentToString(): void
    {
        \mt_srand(0xBADD_CAFE);

        for ($i = 0; $i < 50; ++$i) {
            $code = self::createRandomCode();
            $pathname = self::createFile($code);

            try {
                foreach (["\n", "\r\n"] as $needle) {
                    self::assertContentsMatch(
                        new StringContent($code),
                        new FileContent($pathname, chunkSize: 3, sliceSize: 5),
                        $needle,
                        $code,
                    );
                }
            } finally {
                @\unlink($pathname);
            }
        }
    }

    #[TestDox('Reading a non-existent file is not allowed')]
    public function testReadsNonExistentFile(): void
    {
        $content = new FileContent(__DIR__ . '/non-existent-file.txt');

        $this->expectException(SourceNotReadableException::class);

        $content->getSize();
    }

    /**
     * @param non-empty-string $needle
     */
    private static function assertContentsMatch(
        ContentInterface $expected,
        ContentInterface $actual,
        string $needle,
        string $code,
    ): void {
        $message = \sprintf('Invalid %s search inside the %s source', \var_export($needle, true), \var_export($code, true));

        self::assertSame($expected->getSize(), $actual->getSize(), $message);

        for ($offset = 0, $size = $expected->getSize(); $offset <= $size + 1; ++$offset) {
            self::assertSame($expected->findBefore($needle, $offset), $actual->findBefore($needle, $offset), $message);
            self::assertSame($expected->findAfter($needle, $offset), $actual->findAfter($needle, $offset), $message);
            self::assertSame($expected->countBefore($needle, $offset), $actual->countBefore($needle, $offset), $message);

            foreach ([0, 1, 7] as $length) {
                self::assertSame($expected->read($offset, $length), $actual->read($offset, $length), $message);
            }
        }
    }

    private static function createRandomCode(): string
    {
        $alphabet = ['a', 'bb', 'ccc', "\n", "\r\n", "\n\r", "\r", ' '];

        $result = '';

        for ($i = 0, $size = \mt_rand(0, 12); $i < $size; ++$i) {
            $result .= $alphabet[\mt_rand(0, \count($alphabet) - 1)];
        }

        return $result;
    }

    /**
     * @return non-empty-string
     */
    private static function createFile(string $content): string
    {
        $pathname = \tempnam(\sys_get_temp_dir(), 'phplrt-source-');

        if ($pathname === false || \file_put_contents($pathname, $content) === false) {
            self::fail('Unable to create a temporary source file');
        }

        return $pathname;
    }
}
