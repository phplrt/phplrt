<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap;

use Phplrt\Contracts\Source\ReadableInterface;

class Entry implements EntryInterface
{
    /**
     * @var array
     */
    private array $mappings = [];

    /**
     * @param ReadableInterface $source
     */
    public function __construct(
        private readonly ReadableInterface $source
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getSource(): ReadableInterface
    {
        return $this->source;
    }

    /**
     * {@inheritDoc}
     */
    public function getMappings(): iterable
    {
        foreach ($this->mappings as [$from, $to]) {
            yield $from => $to;
        }
    }
}
