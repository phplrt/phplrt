<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Version3\Entry;

class UnmappedEntry
{
    /**
     * @param int $line
     * @param int $column
     */
    public function __construct(
        public readonly int $line,
        public readonly int $column,
    ) {
    }
}
