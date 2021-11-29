<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Version3\Entry;

use Phplrt\Contracts\Source\ReadableInterface;

class Entry extends UnmappedEntry
{
    /**
     * @param int $line
     * @param int $column
     * @param ReadableInterface $source
     * @param int $sourceLine
     * @param int $sourceColumn
     */
    public function __construct(
        int $line,
        int $column,
        public readonly ReadableInterface $source,
        public readonly int $sourceLine,
        public readonly int $sourceColumn,
    ) {
        parent::__construct($line, $column);
    }
}
