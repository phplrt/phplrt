<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Generator\Version3;

interface CodecInterface
{
    /**
     * @param array<int> $value
     * @return string
     */
    public function encode(array $value): string;

    /**
     * @param string $string
     * @return array<int>
     */
    public function decode(string $string): array;
}
