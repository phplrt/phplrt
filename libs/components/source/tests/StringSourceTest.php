<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Exception\NegativeOffsetException;
use Phplrt\Source\Exception\NonPositiveBytesCountException;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class StringSourceTest extends TestCase
{
    public function testConstructor(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        Assert::same($source->content, $content);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        Assert::same($source->content, $content);
    }

    public function testContentIsTheWholeSource(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        Assert::same($source->read(0, 4), 'test');

        Assert::same($source->content, $content);
        Assert::same($source->content, $content);
    }

    public function testReadsTheWholeContent(): void
    {
        $content = 'test content';
        $source = new StringSource($content);

        Assert::same($source->read(0, 1024), $content);
    }

    public function testReadsByChunks(): void
    {
        $source = new StringSource('test content');

        Assert::same($source->read(0, 4), 'test');
        Assert::same($source->read(4, 1024), ' content');
    }

    public function testReadsInAnArbitraryOrder(): void
    {
        $source = new StringSource('test content');

        Assert::same($source->read(5, 1024), 'content');
        Assert::same($source->read(0, 4), 'test');
        Assert::same($source->read(5, 1024), 'content');
    }

    public function testReadsNothingBeyondTheEnd(): void
    {
        $source = new StringSource('test content');

        Assert::same($source->read(12, 1024), '');
        Assert::same($source->read(1024, 1024), '');
    }

    public function testFailsInCaseOfNegativeOffset(): void
    {
        $source = new StringSource('test content');

        Expect::exception(NegativeOffsetException::class);

        $source->read(-1, 1024);
    }

    public function testFailsInCaseOfNonPositiveReadSize(): void
    {
        Expect::exception(NonPositiveBytesCountException::class);

        (new StringSource('test content'))->read(0, 0);
    }

    public function testReadingDoesNotOpenAnyResource(): void
    {
        $source = new StringSource('test content');

        $before = \count(\get_resources('stream'));

        $source->read(0, 1024);

        Assert::count(\get_resources('stream'), $before);
    }

    public function testEmptyContent(): void
    {
        $source = new StringSource('');

        Assert::same($source->content, '');
        Assert::same($source->read(0, 1024), '');
    }
}
