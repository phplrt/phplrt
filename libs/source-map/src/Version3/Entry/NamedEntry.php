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

class NamedEntry extends Entry
{
    /**
     * @param int $line
     * @param int $column
     * @param ReadableInterface $source
     * @param int $sourceLine
     * @param int $sourceColumn
     * @param string $name
     */
    public function __construct(
        int $line,
        int $column,
        ReadableInterface $source,
        int $sourceLine,
        int $sourceColumn,
        public readonly string $name
    ) {
        parent::__construct($line, $column, $source, $sourceLine, $sourceColumn);
    }
}
