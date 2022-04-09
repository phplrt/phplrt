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

class LineReader
{
    /**
     * @var array|string[]
     */
    private array $lines;

    /**
     * @param ReadableInterface $source
     */
    public function __construct(ReadableInterface $source)
    {
        $filter = fn (string $line) => \trim($line, "\r\0");

        $this->lines = \array_map($filter, \explode("\n", $source->getContents()));
    }

    /**
     * @param int $line
     * @return string
     */
    public function readLine(int $line): string
    {
        return $this->lines[$line - 1] ?? '';
    }

    /**
     * @param int $from
     * @param int $to
     * @return iterable
     */
    public function readLines(int $from, int $to): iterable
    {
        [$from, $to] = [\max(1, $from), \max(1, $to)];

        [$from, $to] = $from > $to ? [$to, $from] : [$from, $to];

        for ($i = $from; $i <= $to; ++$i) {
            if (! isset($this->lines[$i - 1])) {
                break;
            }

            yield $i => $this->readLine($i);
        }
    }
}
