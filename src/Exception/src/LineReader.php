<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Reader;
use Phplrt\Source\ReaderInterface;

/**
 * @deprecated This class is deprecated since 4.0. Please use {@see ReaderInterface} implementation instead.
 */
class LineReader
{
    /**
     * @var ReaderInterface
     */
    private ReaderInterface $reader;

    /**
     * @param ReadableInterface $source
     */
    public function __construct(ReadableInterface $source)
    {
        $this->reader = new Reader($source);
    }

    /**
     * @param positive-int $line
     * @return string
     *
     * @deprecated This method is deprecated since 4.0. Please use {@see ReaderInterface::line()} instead.
     */
    public function readLine(int $line): string
    {
        return $this->reader->line($line);
    }

    /**
     * @param positive-int $from
     * @param positive-int $to
     * @return iterable<positive-int, string>
     *
     * @deprecated This method is deprecated since 4.0. Please use {@see ReaderInterface::lines()} instead.
     */
    public function readLines(int $from, int $to): iterable
    {
        return $this->reader->lines($from, $to);
    }
}
