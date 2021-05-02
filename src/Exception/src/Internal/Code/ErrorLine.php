<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception\Internal\Code;

/**
 * @internal ErrorLine is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Exception
 */
class ErrorLine extends Line
{
    /**
     * @var int
     */
    private int $from = 1;

    /**
     * @var int
     */
    private int $to = 1;

    /**
     * @return positive-int
     */
    public function getFrom(): int
    {
        return $this->from;
    }

    /**
     * @return positive-int
     */
    public function getTo(): int
    {
        return $this->to;
    }
}
