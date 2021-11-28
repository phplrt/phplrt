<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Generator;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\SourceMap\EntryInterface;
use Phplrt\SourceMap\ResultPositionInterface;
use Phplrt\SourceMap\SourcePositionInterface;

/**
 * Source Map Revision 3 proposal implementation.
 *
 * @link https://sourcemaps.info/spec.html
 */
class Version3 extends Generator
{
    /**
     * Encode <, >, ', &, and " characters in the JSON, making it also safe to
     * be embedded into HTML.
     *
     * @var positive-int|0
     */
    private const JSON_ENCODING_FLAGS = \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_AMP | \JSON_HEX_QUOT;

    /**
     * Source Map specification version.
     *
     * @var positive-int
     */
    private const SPEC_VERSION = 3;

    /**
     * @param EntryInterface $entry
     * @return string
     * @throws \JsonException
     */
    public function generate(EntryInterface $entry): string
    {
        $index = $this->getSourcesIndex($entry);

        return $this->toJson([
            'version' => self::SPEC_VERSION,
            'file' => $this->getFileName($entry->getSource()),
            'sourceRoot' => '',
            'source' => $this->getSources($index),
            'sourcesContent' => $this->getSourcesContent($index),
            'names' => [], // TODO
            'mappings' => '', // TODO
        ]);
    }

    /**
     * @param EntryInterface $entry
     * @return \SplObjectStorage<ReadableInterface, positive-int|0>
     */
    private function getSourcesIndex(EntryInterface $entry): \SplObjectStorage
    {
        $storage = new \SplObjectStorage();
        $index = 0;

        /**
         * @psalm-suppress InvalidArgument
         * @var SourcePositionInterface $from
         * @var ResultPositionInterface $to
         */
        foreach ($entry->getMappings() as $from => $_) {
            if (!$storage->offsetExists($from->getSource())) {
                $storage->attach($from->getSource(), $index++);
            }
        }

        return $storage;
    }

    /**
     * @param array $payload
     * @return non-empty-string
     * @throws \JsonException
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    protected function toJson(array $payload): string
    {
        return \json_encode($payload, self::JSON_ENCODING_FLAGS | \JSON_THROW_ON_ERROR);
    }

    /**
     * @param ReadableInterface $source
     * @return non-empty-string
     */
    private function getFileName(ReadableInterface $source): string
    {
        if ($source instanceof FileInterface) {
            return $source->getPathname();
        }

        return 'file://' . $source->getHash();
    }

    /**
     * @param \SplObjectStorage<ReadableInterface, positive-int|0> $index
     * @return array<non-empty-string>
     */
    private function getSources(\SplObjectStorage $index): array
    {
        $result = [];

        foreach ($index as $source) {
            $result[$index->getInfo()] = $this->getFileName($source);
        }

        \ksort($result);

        return $result;
    }

    /**
     * @param \SplObjectStorage<ReadableInterface, positive-int|0> $sources
     * @return array<string>
     *
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedMethodCall
     */
    private function getSourcesContent(\SplObjectStorage $index): array
    {
        $result = [];

        foreach ($index as $source) {
            $result[$index->getInfo()] = $source->getContents();
        }

        \ksort($result);

        return $result;
    }
}
