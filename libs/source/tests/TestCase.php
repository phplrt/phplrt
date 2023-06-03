<?php

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Laminas\Diactoros\StreamFactory;
use Phplrt\Source\File;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array
     */
    public function provider(): array
    {
        $factory = new StreamFactory();

        return [
            'File::fromSources + filename'          => [
                function () {
                    return File::fromSources($this->getSources(), $this->getPathname());
                },
            ],
            'File::fromSources'                     => [
                function () {
                    return File::fromSources($this->getSources());
                },
            ],
            'File::fromPathname'                    => [
                function () {
                    return File::fromPathname($this->getPathname());
                },
            ],
            'File::fromSplFileInfo + SplFileInfo'   => [
                function () {
                    return File::fromSplFileInfo(new \SplFileInfo($this->getPathname()));
                },
            ],
            'File::fromPsrStream + filename' => [
                function () use ($factory) {
                    $stream = $factory->createStreamFromFile($this->getPathname());

                    return File::fromPsrStream($stream, $this->getPathname());
                },
            ],
            'File::fromPsrStream' => [
                function () use ($factory) {
                    $stream = $factory->createStreamFromFile($this->getPathname());

                    return File::fromPsrStream($stream);
                },
            ],
            'File::fromResource + filename' => [
                function () {
                    $resource = \fopen($this->getPathname(), 'rb');

                    return File::fromResource($resource, $this->getPathname());
                },
            ],
            'File::fromResource' => [
                function () {
                    return File::fromResource(\fopen($this->getPathname(), 'rb'));
                },
            ],
        ];
    }

    /**
     * @return string
     */
    protected function getSources(): string
    {
        return \file_get_contents($this->getPathname());
    }

    /**
     * @return string
     */
    abstract protected function getPathname(): string;
}
