<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
final class ExceptionTest extends TestCase
{
    public static function exceptionDataProvider(): iterable
    {
        foreach (\glob(__DIR__ . '/../src/Exception/*.php') ?: [] as $pathname) {
            $class = 'Phplrt\\Source\\Exception\\' . \basename($pathname, '.php');

            yield $class => [$class];
        }
    }

    #[DataProvider('exceptionDataProvider')]
    public function testIsPartOfTheSourceContract(string $class): void
    {
        Assert::true(\is_subclass_of($class, SourceExceptionInterface::class));
    }

    #[DataProvider('exceptionDataProvider')]
    public function testStandsForOneSituationAlone(string $class): void
    {
        $reflection = new \ReflectionClass($class);

        Assert::true($reflection->isAbstract() || $reflection->isFinal());
    }
}
