<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Snippet\Reader;

use Phplrt\Exception\Snippet\Reader\SourceLineReader;
use Phplrt\Exception\Tests\TestCase;
use Phplrt\Position\PositionFactory;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class ChunkedFileReadingTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string SOURCE = "line 1\nline 2\nline 3\nline 4\nline 5\nline 6\nline 7";

    #[TestDox('A file larger than the chunk size is read by chunks')]
    public function testReadsFileByChunks(): void
    {
        $reader = new SourceLineReader(new PositionFactory(4), chunkSize: 4);
        $pathname = self::createFile(self::SOURCE);

        try {
            $source = new FileSource($pathname);

            self::assertSame([
                ' #2@7: line 2',
                ' #3@14: line 3',
                '>#4@21:3-7: line 4',
                '>#5@28:1-7: line 5',
                '>#6@35:1-4: line 6',
                ' #7@42: line 7',
            ], self::describe($reader->read($source, 23, 15, 2)));
        } finally {
            @\unlink($pathname);
        }
    }

    #[TestDox('Reading any fragment of any file by chunks is equivalent to reading it as a whole')]
    public function testChunkedFileReadingIsEquivalentToTheWholeOne(): void
    {
        $reader = new SourceLineReader(new PositionFactory(4), chunkSize: 4);

        \mt_srand(0x0C0F_FEE0);

        for ($i = 0; $i < 25; ++$i) {
            $code = self::createRandomCode();
            $pathname = self::createFile($code);

            try {
                for ($offset = -1, $size = \strlen($code); $offset <= $size + 1; ++$offset) {
                    foreach ([0, 1, 3] as $lines) {
                        foreach ([0, 5, 21] as $length) {
                            self::assertSame(
                                self::describe($reader->read(new StringSource($code), $offset, $length, $lines)),
                                self::describe($reader->read(new FileSource($pathname), $offset, $length, $lines)),
                                \sprintf(
                                    'Invalid snippet of the %s file at offset %d of length %d',
                                    \var_export($code, true),
                                    $offset,
                                    $length,
                                ),
                            );
                        }
                    }
                }
            } finally {
                @\unlink($pathname);
            }
        }
    }

    private static function createRandomCode(): string
    {
        $alphabet = ['a', 'bb', 'ccc', "\n", "\r\n", "\n\r", "\r", ' '];

        $result = '';

        for ($i = 0, $size = \mt_rand(0, 16); $i < $size; ++$i) {
            $result .= $alphabet[\mt_rand(0, \count($alphabet) - 1)];
        }

        return $result;
    }

    /**
     * @return non-empty-string
     */
    private static function createFile(string $content): string
    {
        $pathname = \tempnam(\sys_get_temp_dir(), 'phplrt-snippet-');

        if ($pathname === false || \file_put_contents($pathname, $content) === false) {
            self::fail('Unable to create a temporary source file');
        }

        return $pathname;
    }
}
