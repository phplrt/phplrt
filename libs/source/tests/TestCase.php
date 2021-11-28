<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\Tests;

use Phplrt\Source\File;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array
     */
    public function provider(): array
    {
        return [
            'File::fromSource + filename'          => [
                function () {
                    return File::fromSource($this->getSources(), $this->getPathname());
                },
            ],
            'File::fromSource'                     => [
                function () {
                    return File::fromSource($this->getSources());
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
            'File::fromResource + filename' => [
                function () {
                    $resource = \fopen($this->getPathname(), 'rb');

                    return File::fromResourceStream($resource, $this->getPathname());
                },
            ],
            'File::fromResource' => [
                function () {
                    return File::fromResourceStream(\fopen($this->getPathname(), 'rb'));
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
