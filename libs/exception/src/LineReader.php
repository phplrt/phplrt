<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Source\ReadableInterface;

class LineReader
{
    /**
     * @var array<int<0, max>, string>
     */
    private array $lines;

    /**
     * @param ReadableInterface $source
     */
    public function __construct(ReadableInterface $source)
    {
        $filter = static fn(string $line): string => \trim($line, "\r\0");

        $this->lines = \array_map($filter, \explode("\n", $source->getContents()));
    }

    /**
     * @param int<1, max> $line
     * @return string
     */
    public function readLine(int $line): string
    {
        return $this->lines[$line - 1] ?? '';
    }

    /**
     * @param int<1, max> $from
     * @param int<1, max> $to
     * @return iterable<int<1, max>, string>
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
