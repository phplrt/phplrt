<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap;

use Phplrt\SourceMap\Mapping\OriginalMappingInterface;

/**
 * Interface for provide a way of mapping (line, column) positions back to
 * positions in the original (uncompiled) source code.
 */
interface SourceMappingInterface
{
    /**
     * Returns the original mapping for the line number and column position
     * found in the source map.
     *
     * Returns {@see null} if none is found.
     *
     * @param positive-int $line The line number, with the first being '1'.
     * @param positive-int $column The column index, with the first being '1'.
     * @return OriginalMappingInterface|null
     */
    public function originalMappingFor(int $line, int $column): ?OriginalMappingInterface;
}
