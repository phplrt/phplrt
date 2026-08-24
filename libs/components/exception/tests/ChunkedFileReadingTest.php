<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Analysis\FailureInterval;
use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Exception\Snippet\SourceLine;
use Phplrt\Exception\SnippetReader;
use Phplrt\Position\Position;
use Phplrt\Position\PositionFactory;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/exception')]
final class ChunkedFileReadingTest extends TestCase
{
    private const string SOURCE = "line 1\nline 2\nline 3\nline 4\nline 5\nline 6\nline 7";

    #[TestDox('A file larger than the chunk size is read by chunks')]
    public function testReadsFileByChunks(): void
    {
        $reader = new SnippetReader(new PositionFactory(4), chunkSize: 4);
        $pathname = self::createFile(self::SOURCE);

        try {
            $source = new FileSource($pathname);

            self::assertSame([
                ' #2@7: line 2',
                ' #3@14: line 3',
                '>#4@21:2-6: line 4',
                '>#5@28:0-6: line 5',
                '>#6@35:0-3: line 6',
                ' #7@42: line 7',
            ], self::describe(self::read($reader, $source, new FailureInterval(23, 15), 2)));
        } finally {
            @\unlink($pathname);
        }
    }

    #[TestDox('Reading any fragment of any file by chunks is equivalent to reading it as a whole')]
    public function testChunkedFileReadingIsEquivalentToTheWholeOne(): void
    {
        $reader = new SnippetReader(new PositionFactory(4), chunkSize: 4);

        \mt_srand(0x0C0F_FEE0);

        for ($i = 0; $i < 25; ++$i) {
            $code = self::createRandomCode();
            $pathname = self::createFile($code);

            try {
                for ($offset = -1, $size = \strlen($code); $offset <= $size + 1; ++$offset) {
                    foreach ([0, 1, 3] as $lines) {
                        foreach ([0, 5, 21] as $length) {
                            $fragment = new FailureInterval(\max(0, $offset), $length);

                            self::assertSame(
                                self::describe(self::read($reader, new StringSource($code), $fragment, $lines)),
                                self::describe(self::read($reader, new FileSource($pathname), $fragment, $lines)),
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

    private static function read(
        SnippetReader $reader,
        ReadableInterface $source,
        FailureInterval $fragment,
        int $lines,
    ): array {
        return $reader->read(new FailureResult(
            class: '',
            message: '',
            source: $source,
            position: new Position(),
            interval: $fragment,
        ), $lines);
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

    private static function createFile(string $content): string
    {
        $pathname = \tempnam(\sys_get_temp_dir(), 'phplrt-snippet-');

        if ($pathname === false || \file_put_contents($pathname, $content) === false) {
            self::fail('Unable to create a temporary source file');
        }

        return $pathname;
    }
}
