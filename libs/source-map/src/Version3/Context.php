<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Version3;

final class Context
{
    /**
     * @param int $line
     * @param int $column
     * @param int $sourceFileIndex
     * @param int $sourceLine
     * @param int $sourceColumn
     * @param int $sourceName
     */
    public function __construct(
        public int $line = 0,
        public int $column = 0,
        public int $sourceFileIndex = 0,
        public int $sourceLine = 0,
        public int $sourceColumn = 0,
        public int $sourceName = 0,
    ) {
    }
}
