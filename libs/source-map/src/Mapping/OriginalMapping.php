<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Mapping;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\SourceMap\Mapping\OriginalMappingInterface;

class OriginalMapping extends Mapping implements OriginalMappingInterface
{
    /**
     * @param positive-int $line
     * @param positive-int $column
     */
    public function __construct(
        public readonly ReadableInterface $source,
        int $line = PositionInterface::MIN_LINE,
        int $column = PositionInterface::MIN_COLUMN,
    ) {
        parent::__construct($line, $column);
    }

    /**
     * {@inheritDoc}
     */
    public function getSource(): ReadableInterface
    {
        return $this->source;
    }
}
