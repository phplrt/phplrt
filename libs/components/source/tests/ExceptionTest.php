<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use PHPUnit\Framework\Attributes\DataProvider;

final class ExceptionTest extends TestCase
{
    /**
     * @return iterable<non-empty-string, array{class-string}>
     */
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
        self::assertTrue(\is_subclass_of($class, SourceExceptionInterface::class));
    }
}
