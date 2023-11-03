<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\File;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;

class ErrorsTestCase extends TestCase
{
    /**
     * @return void
     * @throws NotReadableException
     */
    public function testFileNotFound(): void
    {
        $file = __DIR__ . '/not-exists.txt';

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('File "' . $file . '" not found');

        File::fromPathname($file);
    }

    /**
     * @return string
     */
    protected function getPathname(): string
    {
        return __DIR__ . '/resources/example.txt';
    }
}
