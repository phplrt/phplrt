<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Class Util
 */
class Util
{
    /**
     * @var array|string[]
     */
    private array $lines = [];

    /**
     * @var ReadableInterface
     */
    private ReadableInterface $source;

    /**
     * Content constructor.
     *
     * @param ReadableInterface $source
     */
    public function __construct(ReadableInterface $source)
    {
        $this->source = $source;

        $this->lines = \array_map($this->filter(), \explode("\n", $source->getContents()));
    }

    /**
     * @return \Closure
     */
    private function filter(): \Closure
    {
        return fn (string $line) => \trim($line, "\r\0");
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
     * {@inheritDoc}
     */
    public function readLines(int $from, int $to): iterable
    {
        $from = \max(1, $from);
        $to = \max($from, $to);

        for ($i = $from; $i <= $to; ++$i) {
            if (! isset($this->lines[$i - 1])) {
                break;
            }

            yield $i => $this->readLine($i);
        }
    }
}
