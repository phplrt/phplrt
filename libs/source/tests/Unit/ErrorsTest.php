<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests\Unit;

use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\File;

class ErrorsTest extends TestCase
{
    public function testFileNotFound(): void
    {
        $file = __DIR__ . '/not-exists.txt';

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('File "' . $file . '" not found');

        File::fromPathname($file);
    }

    protected static function getPathname(): string
    {
        return __DIR__ . '/resources/example.txt';
    }
}
