<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Grammar;

use Phplrt\Contracts\Grammar\TerminalInterface;

abstract class Terminal extends Rule implements TerminalInterface
{
    /**
     * Terminal constructor.
     *
     * @param bool $keep
     */
    public function __construct(
        public readonly bool $keep = true
    ) {
    }

    /**
     * @return bool
     */
    public function isKeep(): bool
    {
        return $this->keep;
    }
}
