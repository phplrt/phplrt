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

final class Reader implements ReaderInterface
{
    /**
     * @param ReadableInterface $source
     */
    public function __construct(
        private readonly ReadableInterface $source
    ) {
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
        assert($line > 0, new \InvalidArgumentException(
            'Line should be greater than 0, but ' . $line . ' passed'
        ));

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
        assert($from > 0, new \InvalidArgumentException(
            'Line $from argument should be greater than 0, but ' . $from . ' passed'
        ));

        assert($to > 0, new \InvalidArgumentException(
            'Line $to argument should be greater than 0, but ' . $to . ' passed'
        ));

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
