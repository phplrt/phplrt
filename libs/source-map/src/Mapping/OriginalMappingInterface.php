<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Mapping;

use Phplrt\Contracts\Source\ReadableInterface;

interface OriginalMappingInterface extends MappingInterface
{
    /**
     * @return ReadableInterface
     */
    public function getSource(): ReadableInterface;
}
