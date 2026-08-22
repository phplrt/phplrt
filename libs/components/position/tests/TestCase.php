<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private const string TEMP_DIRECTORY = __DIR__ . '/temp';

    protected string $temp {
        get => $this->temp ??= self::TEMP_DIRECTORY
            . \DIRECTORY_SEPARATOR
            . \uniqid('phplrt_test_', true) . '.txt';
    }

    /**
     * The source code samples every calculation is checked against.
     *
     * @return iterable<non-empty-string, array{string}>
     */
    public static function sourceProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'single line' => ['example'];
        yield 'single break' => ["\n"];
        yield 'leading breaks' => ["\n\n\nexample"];
        yield 'trailing breaks' => ["example\n\n\n"];
        yield 'unix breaks' => ["first\nsecond\nthird"];
        yield 'windows breaks' => ["first\r\nsecond\r\nthird"];
        yield 'empty lines' => ["first\n\n\nfourth\n"];
        yield 'utf-8' => ["привет\nмир\n"];
    }

    /**
     * The numbers of bytes read at once, including the ones splitting the
     * source code in the middle of a line.
     *
     * @return iterable<non-empty-string, array{int<1, max>}>
     */
    public static function chunkSizeProvider(): iterable
    {
        yield '1 byte' => [1];
        yield '3 bytes' => [3];
        yield '8 bytes' => [8];
        yield 'default' => [65536];
    }

    /**
     * @return iterable<non-empty-string, array{string, int<1, max>}>
     */
    public static function sourceAndChunkSizeProvider(): iterable
    {
        foreach (self::sourceProvider() as $source => [$code]) {
            foreach (self::chunkSizeProvider() as $chunk => [$size]) {
                yield $source . ' by ' . $chunk => [$code, $size];
            }
        }
    }

    /**
     * The line number the given offset of the source code is located at.
     *
     * @param int<0, max> $offset
     * @return int<1, max>
     */
    protected static function calculateLine(string $code, int $offset): int
    {
        return \substr_count(\substr($code, 0, $offset), "\n") + 1;
    }

    /**
     * The column number the given offset of the source code is located at.
     *
     * @param int<0, max> $offset
     * @return int<1, max>
     */
    protected static function calculateColumn(string $code, int $offset): int
    {
        $head = \substr($code, 0, $offset);
        $delimiter = \strrpos($head, "\n");

        return $delimiter === false
            ? \strlen($head) + 1
            : \strlen($head) - $delimiter;
    }

    /**
     * Creates a resource stream that cannot be rewound and already holds the
     * given content.
     *
     * @return resource
     */
    protected function createNonSeekableResource(string $content = '')
    {
        $pair = @\stream_socket_pair(\STREAM_PF_INET, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);

        if ($pair === false) {
            self::markTestSkipped('The platform does not support socket pairs');
        }

        [$read, $write] = $pair;

        \fwrite($write, $content);
        \fclose($write);

        return $read;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (\is_file($this->temp)) {
            \unlink($this->temp);
        }
    }
}
