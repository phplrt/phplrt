<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\ResourceSource;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Test;

#[Test]
final class VirtualSourceTest extends TestCase
{
    public function testPathnameProperty(): void
    {
        $source = new VirtualSource('virtual/file.php', new StringSource('test content'));

        Assert::same($source->pathname, 'virtual/file.php');
        Assert::instanceOf($source, FileInterface::class);
    }

    public function testReadsTheContentOfTheSourceItWraps(): void
    {
        $source = new VirtualSource('virtual/file.php', new StringSource('test content'));

        Assert::same($source->content, 'test content');
    }

    public function testReadsThroughTheSourceItWraps(): void
    {
        $source = new VirtualSource('virtual/file.php', new StringSource('test content'));

        Assert::same($source->read(0, 1024), 'test content');
        Assert::same($source->read(5, 1024), 'content');
    }

    public function testWrapsAFileOfItsOwn(): void
    {
        \file_put_contents($this->temp, 'test content');

        $source = new VirtualSource('virtual/file.php', new ResourceSource(
            \fopen($this->temp, 'rb'),
            autoclose: true,
        ));

        Assert::same($source->pathname, 'virtual/file.php');
        Assert::same($source->content, 'test content');
    }

    public function testWrapsAnotherVirtualFile(): void
    {
        $source = new VirtualSource('outer.php', new VirtualSource(
            'inner.php',
            new StringSource('test content'),
        ));

        Assert::same($source->pathname, 'outer.php');
        Assert::same($source->content, 'test content');
    }

    public function testEmptyContent(): void
    {
        $source = new VirtualSource('virtual/file.php', new StringSource());

        Assert::same($source->content, '');
        Assert::same($source->read(0, 1024), '');
    }
}
