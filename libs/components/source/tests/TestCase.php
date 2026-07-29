<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private const string TEMP_DIRECTORY = __DIR__ . '/temp';

    protected string $temp {
        get => $this->temp ??= self::TEMP_DIRECTORY
            . \DIRECTORY_SEPARATOR
            . \uniqid('phplrt_test_', true) . '.txt';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (\is_file($this->temp)) {
            \unlink($this->temp);
        }
    }
}
