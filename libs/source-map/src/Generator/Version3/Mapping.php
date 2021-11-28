<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Generator\Version3;

final class Mapping
{
    /**
     * @param CodecInterface $codec
     */
    public function __construct(
        private readonly CodecInterface $codec = new Base64VlqCodec()
    ) {
    }

    /**
     * @param array<array<int>> $mapping
     * @return string
     */
    public function encode(array $mapping): string
    {
        $segments = [];

        foreach ($mapping as $segment) {
            $current = [];
            foreach ($segment as $value) {
                $current[] = $this->codec->encode($value);
            }

            $segments[] = \implode(',', $current);
        }

        return \implode(';', $segments);
    }

    /**
     * @param string $mapping
     * @return array<array<int>>
     */
    public function decode(string $mapping): array
    {
        $result = [];

        foreach (\explode(';', $mapping) as $i => $segment) {
            $result[$i] = [];
            foreach (\explode(',', $segment) as $chunk) {
                $result[$i][] = $this->codec->decode($chunk);
            }
        }

        return $result;
    }
}
