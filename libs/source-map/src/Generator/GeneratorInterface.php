<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Generator;

use Phplrt\SourceMap\EntryInterface;

interface GeneratorInterface
{
    /**
     * @param EntryInterface $entry
     * @return string
     */
    public function generate(EntryInterface $entry): string;
}
