<?php

declare(strict_types=1);

namespace Phplrt\Position\Tests;

use Phplrt\Position\PositionFactory;
use Phplrt\Source\FileSource;
use Phplrt\Source\ResourceSource;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SourceReadingTest extends TestCase
{
    private const CODE = "first\nsecond\nthird";

    public function testSourceIsReadFromItsBeginning(): void
    {
        $factory = new PositionFactory(1);
        $source = new StringSource(self::CODE);

        $position = $factory->createFromOffset($source, 7);

        Assert::same($position->line, 2);
        Assert::same($position->column, 2);
        Assert::same($source->read(7, 5), 'econd');
    }

    public function testFileIsReadInAnArbitraryOrder(): void
    {
        \file_put_contents($this->temp, self::CODE);

        $factory = new PositionFactory(1);
        $source = new FileSource($this->temp);

        Assert::same($source->read(0, 5), 'first');

        $position = $factory->createFromOffset($source, 13);

        Assert::same($position->line, 3);
        Assert::same($position->column, 1);
        Assert::same($source->read(5, 7), "\nsecond");
    }

    public function testVirtualFileIsReadFromItsOwnSource(): void
    {
        $factory = new PositionFactory();
        $source = VirtualSource::createFromString('example.txt', self::CODE);

        $position = $factory->createFromOffset($source, 13);

        Assert::same($position->line, 3);
        Assert::same($position->column, 1);
    }

    public function testNonSeekableSourceIsReadFromItsBeginning(): void
    {
        $factory = new PositionFactory();
        $source = new ResourceSource($this->createNonSeekableResource(self::CODE));

        $position = $factory->createFromOffset($source, \PHP_INT_MAX);

        Assert::same($position->line, 3);
        Assert::same($position->column, 6);
    }

    public function testNonSeekableSourceIsReadAfterAPartOfItHasBeenTaken(): void
    {
        $factory = new PositionFactory();
        $source = (new ResourceSource($this->createNonSeekableResource(self::CODE)))
            ->toSeekableSource();

        Assert::same($source->read(0, 5), 'first');

        $position = $factory->createFromOffset($source, 7);

        Assert::same($position->line, 2);
        Assert::same($position->column, 2);
    }
}
