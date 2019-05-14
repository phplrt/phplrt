<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Tests\Stream;

use Phplrt\Stream\Factory;
use Phplrt\Stream\Stream;
use Phplrt\Stream\StreamInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamTestCase
 */
class StreamTestCase extends TestCase
{
    /**
     * @var mixed
     */
    public $tmpnam;

    /**
     * @var Stream
     */
    protected $stream;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->tmpnam = null;
        $this->stream = Factory::fromResource(\fopen('php://memory', 'wb+'));
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        if ($this->tmpnam && file_exists($this->tmpnam)) {
            unlink($this->tmpnam);
        }
    }

    /**
     * @return void
     */
    public function testCanInstantiateWithStreamIdentifier(): void
    {
        $this->assertInstanceOf(StreamInterface::class, $this->stream);
        $this->assertInstanceOf(\Psr\Http\Message\StreamInterface::class, $this->stream);
    }

    /**
     * @return void
     */
    public function testCanInstantiateWithStreamResource(): void
    {
        $resource = \fopen('php://memory', 'wb+');

        $this->assertInstanceOf(StreamInterface::class, Factory::fromResource($resource));
        $this->assertInstanceOf(\Psr\Http\Message\StreamInterface::class, Factory::fromResource($resource));
    }

    /**
     * @return void
     */
    public function testIsReadableReturnsFalseIfStreamIsNotReadable(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        $stream = Factory::fromResource(\fopen($this->tmpnam, 'wb'));
        $this->assertFalse($stream->isReadable());
    }

    /**
     * @return void
     */
    public function testIsWritableReturnsFalseIfStreamIsNotWritable(): void
    {
        $stream = Factory::fromResource(\fopen('php://memory', 'rb'));

        $this->assertFalse($stream->isWritable());
    }

    /**
     * @return void
     */
    public function testToStringRetrievesFullContentsOfStream(): void
    {
        $message = 'foo bar';
        $this->stream->write($message);

        $this->assertSame($message, (string)$this->stream);
    }

    /**
     * @return void
     */
    public function testDetachReturnsResource(): void
    {
        $resource = \fopen('php://memory', 'wb+');
        $stream = Factory::fromResource($resource);
        $this->assertSame($resource, $stream->detach());
    }

    /**
     * @return void
     */
    public function testStringSerializationReturnsEmptyStringWhenStreamIsNotReadable(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $stream = Factory::fromResource(\fopen($this->tmpnam, 'wb'));

        $this->assertSame('', $stream->__toString());
    }

    /**
     * @return void
     */
    public function testCloseClosesResource(): void
    {
        $resource = \fopen('php://memory', 'wb+');

        $type = \get_resource_type($resource);

        Factory::fromResource($resource)->close();

        $this->assertNotSame($type, \get_resource_type($resource));
        //
        // is_resource function returns FALSE for every closed resource.
        //
        $this->assertFalse(\is_resource($resource));
    }

    /**
     * @return void
     */
    public function testCloseUnsetsResource(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);
        $stream->close();

        $this->assertNull($stream->detach());
    }

    /**
     * @return void
     */
    public function testCloseDoesNothingAfterDetach(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);
        $detached = $stream->detach();

        $stream->close();
        $this->assertIsResource($detached);
        $this->assertSame($resource, $detached);
    }

    /**
     * @group 42
     */
    public function testSizeReportsNullWhenNoResourcePresent(): void
    {
        $this->stream->detach();
        $this->assertNull($this->stream->getSize());
    }

    /**
     * @return void
     */
    public function testTellReportsCurrentPositionInResource(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);

        fseek($resource, 2);

        $this->assertSame(2, $stream->tell());
    }

    /**
     * @return void
     */
    public function testTellRaisesExceptionIfResourceIsDetached(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);

        \fseek($resource, 2);
        $stream->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream was previously closed or detached');

        $stream->tell();
    }

    /**
     * @return void
     */
    public function testEofReportsFalseWhenNotAtEndOfStream(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);

        fseek($resource, 2);
        $this->assertFalse($stream->eof());
    }

    /**
     * @return void
     */
    public function testEofReportsTrueWhenAtEndOfStream(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);

        while (! \feof($resource)) {
            \fread($resource, 4096);
        }

        $this->assertTrue($stream->eof());
    }

    /**
     * @return void
     */
    public function testEofReportsTrueWhenStreamIsDetached(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);

        \fseek($resource, 2);
        $stream->detach();
        $this->assertTrue($stream->eof());
    }

    public function testIsSeekableReturnsTrueForReadableStreams(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);

        $this->assertTrue($stream->isSeekable());
    }

    public function testIsSeekableReturnsFalseForDetachedStreams(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);
        $stream->detach();
        $this->assertFalse($stream->isSeekable());
    }

    public function testSeekAdvancesToGivenOffsetOfStream(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull($stream->seek(2));
        $this->assertSame(2, $stream->tell());
    }

    /**
     * @return void
     */
    public function testRewindResetsToStartOfStream(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $this->assertNull($stream->seek(2));
        $stream->rewind();
        $this->assertSame(0, $stream->tell());
    }

    /**
     * @return void
     */
    public function testSeekRaisesExceptionWhenStreamIsDetached(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);
        $stream->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream was previously closed or detached');

        $stream->seek(2);
    }

    /**
     * @return void
     */
    public function testIsWritableReturnsFalseWhenStreamIsDetached(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);
        $stream->detach();
        $this->assertFalse($stream->isWritable());
    }

    /**
     * @return void
     */
    public function testIsWritableReturnsTrueForWritableMemoryStream(): void
    {
        $stream = Factory::fromResource(\fopen('php://temp', 'rb+'));

        $this->assertTrue($stream->isWritable());
    }

    /**
     * @return array
     */
    public function provideDataForIsWritable(): array
    {
        return [
            ['a', true, true],
            ['a+', true, true],
            ['a+b', true, true],
            ['ab', true, true],
            ['c', true, true],
            ['c+', true, true],
            ['c+b', true, true],
            ['cb', true, true],
            ['r', true, false],
            ['r+', true, true],
            ['r+b', true, true],
            ['rb', true, false],
            ['rw', true, true],
            ['w', true, true],
            ['w+', true, true],
            ['w+b', true, true],
            ['wb', true, true],
            ['x', false, true],
            ['x+', false, true],
            ['x+b', false, true],
            ['xb', false, true],
        ];
    }

    /**
     * @dataProvider provideDataForIsWritable
     * @param string $mode
     * @param bool $shouldExist
     * @param bool $flag
     */
    public function testIsWritableReturnsCorrectFlagForMode(string $mode, bool $shouldExist, bool $flag): void
    {
        if ($shouldExist) {
            $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
            \file_put_contents($this->tmpnam, 'FOO BAR');
        } else {
            // "x" modes REQUIRE that file doesn't exist, so we need to find random file name
            $this->tmpnam = $this->findNonExistentTempName();
        }

        $resource = \fopen($this->tmpnam, $mode);
        $stream = Factory::fromResource($resource);
        $this->assertSame($flag, $stream->isWritable());
    }

    /**
     * @return string
     */
    private function findNonExistentTempName(): string
    {
        $tempName = '';

        while (true) {
            $tempName = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phplrt' . \uniqid('', false);

            if (! \file_exists(\sys_get_temp_dir() . $tempName)) {
                break;
            }
        }

        return $tempName;
    }

    /**
     * @return array
     */
    public function provideDataForIsReadable(): array
    {
        return [
            ['a', true, false],
            ['a+', true, true],
            ['a+b', true, true],
            ['ab', true, false],
            ['c', true, false],
            ['c+', true, true],
            ['c+b', true, true],
            ['cb', true, false],
            ['r', true, true],
            ['r+', true, true],
            ['r+b', true, true],
            ['rb', true, true],
            ['rw', true, true],
            ['w', true, false],
            ['w+', true, true],
            ['w+b', true, true],
            ['wb', true, false],
            ['x', false, false],
            ['x+', false, true],
            ['x+b', false, true],
            ['xb', false, false],
        ];
    }

    /**
     * @dataProvider provideDataForIsReadable
     * @param string $mode
     * @param bool $shouldExist
     * @param bool $flag
     */
    public function testIsReadableReturnsCorrectFlagForMode(string $mode, bool $shouldExist, bool $flag): void
    {
        if ($shouldExist) {
            $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
            \file_put_contents($this->tmpnam, 'FOO BAR');
        } else {
            // "x" modes REQUIRE that file doesn't exist, so we need to find random file name
            $this->tmpnam = $this->findNonExistentTempName();
        }

        $resource = \fopen($this->tmpnam, $mode);
        $stream = Factory::fromResource($resource);
        $this->assertSame($flag, $stream->isReadable());
    }

    /**
     * @return void
     */
    public function testWriteRaisesExceptionWhenStreamIsDetached(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);
        $stream->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream was previously closed or detached');

        $stream->write('bar');
    }

    /**
     * @return void
     */
    public function testWriteRaisesExceptionWhenStreamIsNotWritable(): void
    {
        $stream = Factory::fromResource(\fopen('php://memory', 'rb'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream is not writable');

        $stream->write('bar');
    }

    /**
     * @return void
     */
    public function testIsReadableReturnsFalseWhenStreamIsDetached(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb+');
        $stream = Factory::fromResource($resource);
        $stream->detach();

        $this->assertFalse($stream->isReadable());
    }

    /**
     * @return void
     */
    public function testReadRaisesExceptionWhenStreamIsDetached(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'rb');
        $stream = Factory::fromResource($resource);
        $stream->detach();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stream was previously closed or detached');

        $stream->read(4096);
    }

    /**
     * @return void
     */
    public function testReadReturnsEmptyStringWhenAtEndOfFile(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'rb');
        $stream = Factory::fromResource($resource);

        while (! feof($resource)) {
            fread($resource, 4096);
        }

        $this->assertSame('', $stream->read(4096));
    }

    /**
     * @return void
     */
    public function testGetContentsRisesExceptionIfStreamIsNotReadable(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'wb');
        $stream = Factory::fromResource($resource);

        $this->expectException(\RuntimeException::class);

        $stream->getContents();
    }

    /**
     * @return array
     */
    public function invalidResources(): array
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');

        return [
            'null'                => [null],
            'false'               => [false],
            'true'                => [true],
            'int'                 => [1],
            'float'               => [1.1],
            'string-non-resource' => ['foo-bar-baz'],
            'array'               => [
                [
                    \fopen($this->tmpnam, 'rb+'),
                ],
            ],
            'object'              => [
                (object)[
                    'resource' => \fopen($this->tmpnam, 'rb+'),
                ],
            ],
        ];
    }

    /**
     * @group 42
     */
    public function testGetSizeReturnsStreamSize(): void
    {
        $resource = \fopen(__FILE__, 'rb');
        $expected = fstat($resource);
        $stream = Factory::fromResource($resource);
        $this->assertSame($expected['size'], $stream->getSize());
    }

    /**
     * @return void
     */
    public function testRaisesExceptionOnConstructionForNonStreamResources(): void
    {
        $this->expectNotToPerformAssertions();

        $memory = \fopen('php://memory', 'rb');
        \fclose($memory);

        Factory::fromResource($memory);
    }

    /**
     * @return void
     */
    public function testCanReadContentFromNotSeekableResource(): void
    {
        $this->tmpnam = \tempnam(\sys_get_temp_dir(), 'phplrt');
        \file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = \fopen($this->tmpnam, 'rb');
        $stream = $this
            ->getMockBuilder(Stream::class)
            ->setConstructorArgs([$resource])
            ->setMethods(['isSeekable'])
            ->getMock();

        $stream
            ->method('isSeekable')
            ->willReturn(false);

        $this->assertSame('FOO BAR', $stream->__toString());
    }

    /**
     * @group 42
     */
    public function testSizeReportsNullForPhpInputStreams(): void
    {
        $stream = Factory::fromResource(\fopen('php://input', 'rb'));

        $this->assertNull($stream->getSize());
    }
}
