<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Buffer\Tests;

use Phplrt\Contracts\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\Token;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var int
     */
    protected int $bufferSize = 10;

    /**
     * @return array
     */
    public function buffersDataProvider(): array
    {
        return [
            'Generator'        => [
                $this->create(
                    $this->createTokens($this->bufferSize)
                ),
            ],
            'array'            => [
                $this->create([...$this->createTokens($this->bufferSize)]),
            ],
            'IteratorIterator' => [
                $this->create(new \IteratorIterator($this->createTokens($this->bufferSize))),
            ],
            'ArrayIterator'    => [
                $this->create(new \ArrayIterator([...$this->createTokens($this->bufferSize)])),
            ],
        ];
    }

    /**
     * @param iterable $tokens
     * @return BufferInterface
     */
    abstract protected function create(iterable $tokens): BufferInterface;

    /**
     * @param int $count
     * @return \Generator
     */
    private function createTokens(int $count): \Generator
    {
        for ($i = 0; $i < $count; ++$i) {
            yield new Token($i, 'Value#' . $i, $i);
        }
    }

    /**
     * @dataProvider buffersDataProvider
     *
     * @param BufferInterface $buffer
     * @return void
     */
    public function testIsIterable(BufferInterface $buffer): void
    {
        foreach ($buffer as $index => $token) {
            $this->assertInstanceOf(TokenInterface::class, $token);
            $this->assertIsInt($index);
        }
    }

    /**
     * @dataProvider buffersDataProvider
     *
     * @param BufferInterface $buffer
     * @return void
     */
    public function testKeysDoNotIntersect(BufferInterface $buffer): void
    {
        $buffer = \iterator_to_array($buffer, true);

        $this->assertCount(10, $buffer);
    }

    /**
     * @dataProvider buffersDataProvider
     *
     * @param BufferInterface $buffer
     * @return void
     */
    public function testCurrentSameWithIteratorState(BufferInterface $buffer): void
    {
        foreach ($buffer as $token) {
            $this->assertSame($token, $buffer->current());
        }
    }

    /**
     * @dataProvider buffersDataProvider
     *
     * @param BufferInterface $buffer
     * @return void
     */
    public function testKeySameWithIteratorState(BufferInterface $buffer): void
    {
        foreach ($buffer as $index => $token) {
            $this->assertSame($index, $buffer->key());
        }
    }

    /**
     * @dataProvider buffersDataProvider
     *
     * @param BufferInterface $buffer
     * @return void
     */
    public function testRewindable(BufferInterface $buffer): void
    {
        $needle = $buffer->current();

        // Iterate
        foreach ($buffer as $token) {
        }

        $this->assertNotSame($needle, $buffer->current());
        $buffer->rewind();
        $this->assertSame($needle, $buffer->current());
    }

    /**
     * @dataProvider buffersDataProvider
     *
     * @param BufferInterface $buffer
     * @return void
     */
    public function testSeekAhead(BufferInterface $buffer): void
    {
        $buffer->seek($this->bufferSize - 1);

        $needle = $buffer->current();

        $buffer->rewind();

        foreach ($buffer as $item) {
        }

        $this->assertSame($buffer->current(), $needle);
    }

    /**
     * @dataProvider buffersDataProvider
     *
     * @param BufferInterface $buffer
     * @return void
     */
    public function testSeekOverflow(BufferInterface $buffer): void
    {
        $this->expectException(\OutOfRangeException::class);
        $buffer->seek($this->bufferSize + 1000);
    }

    /**
     * @dataProvider buffersDataProvider
     *
     * @param BufferInterface $buffer
     * @return void
     */
    public function testSeekUnderflow(BufferInterface $buffer): void
    {
        $this->expectException(\OutOfRangeException::class);
        $buffer->seek($this->bufferSize + 1000);
    }

    /**
     * @dataProvider buffersDataProvider
     *
     * @param BufferInterface $buffer
     * @return void
     */
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
