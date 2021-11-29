<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Codec;

interface CodecInterface
{
    /**
     * @param list<int> $values
     * @return string
     */
    public function encode(iterable $values): string;

    /**
     * @param string $string
     * @return list<int>
     */
    public function decode(string $string): array;
}
