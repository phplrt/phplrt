<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Version3;

/**
 * Wraps a JsonObject to provide a V3 source map.
 *
 * @psalm-import-type SourceMapV3Output from GeneratorV3
 * @see GeneratorV3
 */
final class SourceMapObject implements \JsonSerializable
{
    /**
     * @param positive-int $version
     * @param string|null $file
     * @param positive-int|null|0 $lineCount
     * @param string|null $sourceRoot
     * @param list<non-empty-string> $sources
     * @param list<string> $sourcesContent
     * @param list<non-empty-string> $names
     * @param string $mappings
     */
    public function __construct(
        public readonly int $version,
        public readonly ?string $file,
        public readonly ?int $lineCount,
        public readonly ?string $sourceRoot,
        public readonly array $sources,
        public readonly array $sourcesContent,
        public readonly array $names,
        public readonly string $mappings,
    ) {
    }

    /**
     * @return SourceMapV3Output
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return SourceMapV3Output
     */
    public function toArray(): array
    {
        $result = [
            'version' => $this->version,
            'sources' => $this->sources,
            'sourcesContent' => $this->sourcesContent,
            'names' => $this->names,
            'mappings' => $this->mappings,
        ];

        if ($this->file !== null) {
            $result['file'] = $this->file;
        }

        if ($this->lineCount !== null) {
            $result['lineCount'] = $this->lineCount;
        }

        if ($this->sourceRoot !== null) {
            $result['sourceRoot'] = $this->sourceRoot;
        }

        return $result;
    }
}
