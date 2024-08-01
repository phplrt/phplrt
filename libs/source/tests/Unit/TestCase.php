<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests\Unit;

use HttpSoft\Message\StreamFactory;
use Phplrt\Source\File;
use Phplrt\Source\Tests\TestCase as UnitTestCase;

abstract class TestCase extends UnitTestCase
{
    public static function provider(): array
    {
        $factory = new StreamFactory();

        return [
            'File::fromSources + filename' => [
                function () {
                    return File::fromSources(static::getSources(), static::getPathname());
                },
            ],
            'File::fromSources' => [
                function () {
                    return File::fromSources(static::getSources());
                },
            ],
            'File::fromPathname' => [
                function () {
                    return File::fromPathname(static::getPathname());
                },
            ],
            'File::fromSplFileInfo + SplFileInfo' => [
                function () {
                    return File::fromSplFileInfo(new \SplFileInfo(static::getPathname()));
                },
            ],
            'File::fromPsrStream + filename' => [
                function () use ($factory) {
                    $stream = $factory->createStreamFromFile(static::getPathname());

                    return File::fromPsrStream($stream, static::getPathname());
                },
            ],
            'File::fromPsrStream' => [
                function () use ($factory) {
                    $stream = $factory->createStreamFromFile(static::getPathname());

                    return File::fromPsrStream($stream);
                },
            ],
            'File::fromResource + filename' => [
                function () {
                    $resource = \fopen(static::getPathname(), 'rb');

                    return File::fromResource($resource, static::getPathname());
                },
            ],
            'File::fromResource' => [
                function () {
                    return File::fromResource(\fopen(static::getPathname(), 'rb'));
                },
            ],
        ];
    }

    protected static function getSources(): string
    {
        return \file_get_contents(static::getPathname());
    }

    abstract protected static function getPathname(): string;
}
