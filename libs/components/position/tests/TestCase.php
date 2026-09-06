<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Testo\Core\Exception\SkipTest;
use Testo\Lifecycle\AfterTest;

/**
 * @property-read string $temp
 */
abstract class TestCase
{
    private const TEMP_DIRECTORY = __DIR__ . '/temp';

    private ?string $tempPathname = null;

    public function __get(string $property): mixed
    {
        return match ($property) {
            'temp' => $this->tempPathname ??= self::TEMP_DIRECTORY
                . \DIRECTORY_SEPARATOR
                . \uniqid('phplrt_test_', true) . '.txt',
            default => throw new \Error(\sprintf(
                'Undefined property %s::$%s',
                static::class,
                $property,
            )),
        };
    }

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

    public static function chunkSizeProvider(): iterable
    {
        yield '1 byte' => [1];
        yield '3 bytes' => [3];
        yield '8 bytes' => [8];
        yield 'default' => [65536];
    }

    public static function sourceAndChunkSizeProvider(): iterable
    {
        foreach (self::sourceProvider() as $source => [$code]) {
            foreach (self::chunkSizeProvider() as $chunk => [$size]) {
                yield $source . ' by ' . $chunk => [$code, $size];
            }
        }
    }

    protected static function calculateLine(string $code, int $offset): int
    {
        return \substr_count(\substr($code, 0, $offset), "\n") + 1;
    }

    protected static function calculateColumn(string $code, int $offset): int
    {
        $head = \substr($code, 0, $offset);
        $delimiter = \strrpos($head, "\n");

        return $delimiter === false
            ? \strlen($head) + 1
            : \strlen($head) - $delimiter;
    }

    protected function createNonSeekableResource(string $content = '')
    {
        $pair = @\stream_socket_pair(\STREAM_PF_INET, \STREAM_SOCK_STREAM, \STREAM_IPPROTO_IP);

        if ($pair === false) {
            throw new SkipTest('The platform does not support socket pairs');
        }

        [$read, $write] = $pair;

        \fwrite($write, $content);
        \fclose($write);

        return $read;
    }

    #[AfterTest]
    protected function tearDown(): void
    {
        if (\is_file($this->temp)) {
            \unlink($this->temp);
        }
    }
}
