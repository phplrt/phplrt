<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Position;

/**
 * Interface ContentInterface
 */
interface ContentInterface
{
    /**
     * @param int $line
     * @return string
     */
    public function line(int $line): string;

    /**
     * @param int $line
     * @param int $before
     * @param int $after
     * @return iterable|string[]
     */
    public function lines(int $line, int $before = 0, int $after = 0): iterable;
}
