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

class Reader implements ReaderInterface
{
    /**
     * @var ReadableInterface
     */
    private ReadableInterface $source;

    /**
     * @param ReadableInterface $source
     */
    public function __construct(ReadableInterface $source)
    {
        $this->source = $source;
    }

    /**
     * @param string $line
     * @return string
     */
    private function escape(string $line): string
    {
        return \rtrim($line, "\n\r");
    }

    /**
     * {@inheritDoc}
     */
    public function line(int $line): string
    {
        assert($line > 0, 'Line should be greater than 0');

        foreach ($this->lines($line, $line) as $current) {
            return $current;
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function lines(int $from, int $to): iterable
    {
        assert($from > 0, 'Line [$from] should be greater than 0');
        assert($to > 0, 'Line [$to] should be greater than 0');

        [$from, $to] = $from > $to ? [$to, $from] : [$from, $to];

        $stream = $this->source->getStream();
        $current = 0;

        while (! \feof($stream)) {
            $line = \fgets($stream);

            if (++$current >= $from) {
                yield $current => $this->escape((string)$line);

                if ($current >= $to) {
                    break;
                }
            }
        }

        \fclose($stream);
    }
}
