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

interface EntryInterface
{
    /**
     * @return ReadableInterface
     */
    public function getSource(): ReadableInterface;

    /**
     * @return iterable
     */
    public function getMappings(): iterable;
}
