<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source;

interface ReaderInterface
{
    /**
     * @param positive-int $line
     * @return string
     */
    public function line(int $line): string;

    /**
     * @param positive-int $from
     * @param positive-int $to
     * @return iterable<positive-int, string>
     */
    public function lines(int $from, int $to): iterable;
}
