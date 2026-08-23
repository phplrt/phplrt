<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Position\PositionFactory;
use Phplrt\Source\FileSource;
use Phplrt\Source\ResourceSource;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;

final class SourceReadingTest extends TestCase
{
    private const string CODE = "first\nsecond\nthird";

    public function testSourceIsReadFromItsBeginning(): void
    {
        $factory = new PositionFactory(1);
        $source = new StringSource(self::CODE);

        $position = $factory->createFromOffset($source, 7);

        self::assertSame(2, $position->line);
        self::assertSame(2, $position->column);
        self::assertSame('econd', $source->read(7, 5));
    }

    public function testFileIsReadInAnArbitraryOrder(): void
    {
        \file_put_contents($this->temp, self::CODE);

        $factory = new PositionFactory(1);
        $source = new FileSource($this->temp);

        self::assertSame('first', $source->read(0, 5));

        $position = $factory->createFromOffset($source, 13);

        self::assertSame(3, $position->line);
        self::assertSame(1, $position->column);
        self::assertSame("\nsecond", $source->read(5, 7));
    }

    public function testVirtualFileIsReadFromItsOwnSource(): void
    {
        // The pathname of a virtual file is not the one of a real file, so
        // the source it has been built over is the only data available.
        $factory = new PositionFactory();
        $source = VirtualSource::createFromString('example.txt', self::CODE);

        $position = $factory->createFromOffset($source, 13);

        self::assertSame(3, $position->line);
        self::assertSame(1, $position->column);
    }

    public function testNonSeekableSourceIsReadFromItsBeginning(): void
    {
        $factory = new PositionFactory();
        $source = new ResourceSource($this->createNonSeekableResource(self::CODE));

        $position = $factory->createFromOffset($source, \PHP_INT_MAX);

        self::assertSame(3, $position->line);
        self::assertSame(6, $position->column);
    }

    public function testNonSeekableSourceIsReadAfterAPartOfItHasBeenTaken(): void
    {
        $factory = new PositionFactory();
        $source = new ResourceSource($this->createNonSeekableResource(self::CODE));

        self::assertSame('first', $source->read(0, 5));

        $position = $factory->createFromOffset($source, 7);

        self::assertSame(2, $position->line);
        self::assertSame(2, $position->column);
    }
}
