<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\Exception\FileNotFoundException;
use Phplrt\Source\FileSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class FileSourceTest extends TestCase
{
    public function testConstructor(): void
    {
        $file = new FileSource($this->temp);

        Assert::same($file->pathname, $this->temp);
    }

    public function testIsExistsPropertyWhenFileExists(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        Assert::true($file->isExists);
    }

    public function testIsExistsPropertyWhenFileNotExists(): void
    {
        $file = new FileSource($this->temp);

        Assert::false($file->isExists);
    }

    public function testIsReadablePropertyWhenFileIsReadable(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        Assert::true($file->isReadable);
    }

    public function testIsReadablePropertyWhenFileNotExists(): void
    {
        $file = new FileSource($this->temp);

        Assert::false($file->isReadable);
    }

    public function testModifiedAtProperty(): void
    {
        \file_put_contents($this->temp, 'test content');
        $expectedTime = \filemtime($this->temp);

        $file = new FileSource($this->temp);

        Assert::same($file->modifiedAt, $expectedTime);
    }

    public function testContentProperty(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new FileSource($this->temp);

        Assert::same($file->content, $content);
    }

    public function testContentIsTheWholeFile(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        Assert::same($file->read(0, 4), 'test');

        Assert::same($file->content, 'test content');
        Assert::same($file->content, 'test content');

        Assert::same($file->read(4, 1024), ' content');
    }

    public function testTheFileIsGivenUpAlongWithTheSource(): void
    {
        \file_put_contents($this->temp, 'first content');

        $file = new FileSource($this->temp);

        Assert::same($file->content, 'first content');

        unset($file);

        Assert::notSame(\file_put_contents($this->temp, 'second content'), false);
        Assert::same(new FileSource($this->temp)->content, 'second content');
    }

    public function testContentPropertyThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        Expect::exception(FileNotFoundException::class);

        $file->content;
    }

    public function testSizeThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        Expect::exception(FileNotFoundException::class);

        $file->size;
    }

    public function testModifiedAtThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        Expect::exception(FileNotFoundException::class);

        $file->modifiedAt;
    }

    public function testSizeProperty(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        Assert::same($file->size, 12);
    }

    public function testReadsTheFileFromTheBeginning(): void
    {
        $content = 'test content';
        \file_put_contents($this->temp, $content);

        $file = new FileSource($this->temp);

        Assert::same($file->read(0, 1024), $content);
    }

    public function testReadsByChunks(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        Assert::same($file->read(0, 4), 'test');
        Assert::same($file->read(4, 1024), ' content');
    }

    public function testReadsInAnArbitraryOrder(): void
    {
        \file_put_contents($this->temp, 'test content');

        $file = new FileSource($this->temp);

        Assert::same($file->read(5, 1024), 'content');
        Assert::same($file->read(0, 4), 'test');
        Assert::same($file->read(5, 1024), 'content');
    }

    public function testDoesNotOpenTheFileUntilItIsRead(): void
    {
        \file_put_contents($this->temp, 'test content');

        $before = \count(\get_resources('stream'));

        $file = new FileSource($this->temp);

        Assert::count(\get_resources('stream'), $before);

        $file->read(0, 1);

        Assert::count(\get_resources('stream'), $before + 1);
    }

    public function testClosesTheFileAlongWithTheSource(): void
    {
        \file_put_contents($this->temp, 'test content');

        $before = \count(\get_resources('stream'));

        $file = new FileSource($this->temp);
        $file->read(0, 1);
        unset($file);

        Assert::count(\get_resources('stream'), $before);
    }

    public function testReadingThrowsWhenFileNotFound(): void
    {
        $file = new FileSource($this->temp);

        Expect::exception(FileNotFoundException::class);

        $file->read(0, 1024);
    }
}
