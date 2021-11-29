<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Version3;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\SourceMap\Mapping\OriginalMappingInterface;
use Phplrt\SourceMap\SourceMappingInterface;
use Phplrt\SourceMap\Version3\Entry\UnmappedEntry;

final class SourceMapping implements SourceMappingInterface
{
    /**
     * @var array<UnmappedEntry>
     */
    private array $entries = [];

    /**
     * @param ReadableInterface $source
     */
    public function __construct(
        public ReadableInterface $source,
    ) {
    }

    /**
     * @param UnmappedEntry $entry
     * @return void
     */
    public function addEntry(UnmappedEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    /**
     * @return iterable<UnmappedEntry>
     */
    public function getEntries(): iterable
    {
        return $this->entries;
    }

    /**
     * @param int $line
     * @param int $column
     * @return OriginalMappingInterface|null
     */
    public function originalMappingFor(int $line, int $column): ?OriginalMappingInterface
    {
        throw new \LogicException(__METHOD__ . ' not implemented yet');
    }
}
