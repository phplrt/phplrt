<?php

declare(strict_types=1);

namespace Phplrt\Buffer\Tests\Unit;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Buffer\Tests\TestCase as BaseTestCase;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\Token;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class TestCase extends BaseTestCase
{
    protected static int $bufferSize = 10;

    public static function buffersDataProvider(): array
    {
        return [
            'Generator' => [
                static::create(self::createTokens((static::$bufferSize))),
            ],
            'array' => [
                static::create([
                    ...self::createTokens((static::$bufferSize)),
                ]),
            ],
            'IteratorIterator' => [
                static::create(new \IteratorIterator(
                    self::createTokens((static::$bufferSize)),
                )),
            ],
            'ArrayIterator' => [
                static::create(new \ArrayIterator([
                    ...self::createTokens((static::$bufferSize)),
                ])),
            ],
        ];
    }

    abstract protected static function create(iterable $tokens): BufferInterface;

    private static function createTokens(int $count): \Generator
    {
        for ($i = 0; $i < $count; ++$i) {
            yield new Token((string) $i, 'Value#' . $i, $i);
        }
    }

    #[DataProvider('buffersDataProvider')]
    public function testIsIterable(BufferInterface $buffer): void
    {
        foreach ($buffer as $index => $token) {
            $this->assertInstanceOf(TokenInterface::class, $token);
            $this->assertIsInt($index);
        }
    }

    #[DataProvider('buffersDataProvider')]
    public function testKeysDoNotIntersect(BufferInterface $buffer): void
    {
        $buffer = \iterator_to_array($buffer, true);

        $this->assertCount(10, $buffer);
    }

    #[DataProvider('buffersDataProvider')]
    public function testCurrentSameWithIteratorState(BufferInterface $buffer): void
    {
        foreach ($buffer as $token) {
            $this->assertSame($token, $buffer->current());
        }
    }

    #[DataProvider('buffersDataProvider')]
    public function testKeySameWithIteratorState(BufferInterface $buffer): void
    {
        foreach ($buffer as $index => $token) {
            $this->assertSame($index, $buffer->key());
        }
    }

    #[DataProvider('buffersDataProvider')]
    public function testRewindable(BufferInterface $buffer): void
    {
        $needle = $buffer->current();

        // Iterate
        foreach ($buffer as $token);

        $this->assertNotSame($needle, $buffer->current());
        $buffer->rewind();
        $this->assertSame($needle, $buffer->current());
    }

    #[DataProvider('buffersDataProvider')]
    public function testSeekAhead(BufferInterface $buffer): void
    {
        $buffer->seek(static::$bufferSize - 1);

        $needle = $buffer->current();

        $buffer->rewind();

        foreach ($buffer as $item);

        $this->assertSame($buffer->current(), $needle);
    }

    #[DataProvider('buffersDataProvider')]
    public function testSeekOverflow(BufferInterface $buffer): void
    {
        $this->expectException(\OutOfRangeException::class);
        $buffer->seek(static::$bufferSize + 1000);
    }

    #[DataProvider('buffersDataProvider')]
    public function testSeekUnderflow(BufferInterface $buffer): void
    {
        $this->expectException(\OutOfRangeException::class);
        $buffer->seek(static::$bufferSize + 1000);
    }

    #[DataProvider('buffersDataProvider')]
    public function testSeekable(BufferInterface $buffer): void
    {
        $needle = [];

        foreach ($buffer as $index => $token) {
            $needle[] = [$index, $token];
        }

        // Direct order
        foreach ($needle as [$index, $token]) {
            $buffer->seek($index);

            $this->assertSame($index, $buffer->key());
            $this->assertSame($token, $buffer->current());
        }

        // Reverse order
        foreach (\array_reverse($needle) as [$index, $token]) {
            $buffer->seek($index);

            $this->assertSame($index, $buffer->key());
            $this->assertSame($token, $buffer->current());
        }
    }
}
