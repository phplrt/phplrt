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
use Phplrt\Source\Factory;
use Phplrt\Source\FactoryInterface;
use Phplrt\SourceMap\Codec\Base64Vlq;
use Phplrt\SourceMap\Codec\CodecInterface;
use Phplrt\SourceMap\Version3\Context;
use Phplrt\SourceMap\Version3\Entry;
use Phplrt\SourceMap\Version3\SourceMapObject;
use Phplrt\SourceMap\Version3\SourceMapObjectParser;
use Phplrt\SourceMap\Version3\SourceMapping;
use Phplrt\SourceMap\Version3\SourcesStorage;

/**
 * @psalm-type SourceMapV3Input = array {
 *  version?: positive-int|mixed,
 *  file?: string|mixed,
 *  lineCount?: positive-int|0|mixed,
 *  sourceRoot?: string|mixed,
 *  sources?: list<string>|mixed,
 *  sourcesContent?: list<string>|mixed,
 *  names?: list<string>|mixed,
 *  mappings?: string|mixed
 * }
 *
 * @psalm-import-type SourceMapV3Output from GeneratorV3
 */
final class ParserV3 implements ParserInterface
{
    /**
     * @var SourceMapObjectParser
     */
    private readonly SourceMapObjectParser $parser;

    /**
     * @var FactoryInterface
     */
    private readonly FactoryInterface $sources;

    /**
     * @var CodecInterface
     */
    private readonly CodecInterface $codec;

    /**
     * Parser constructor.
     */
    public function __construct()
    {
        $this->parser = new SourceMapObjectParser();
        $this->sources = Factory::getInstance();
        $this->codec = new Base64Vlq();
    }

    /**
     * @param string $contents
     * @return SourceMapping
     * @throws \JsonException
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function parse(string $contents): SourceMapping
    {
        return $this->create($this->parser->parse($contents));
    }

    /**
     * @param SourceMapObject $data
     * @return SourceMapping
     */
    private function create(SourceMapObject $data): SourceMapping
    {
        if ($data->version !== 3) {
            throw new \InvalidArgumentException('Unknown version: ' . $data->version);
        }

        if ($data->file !== null && \trim($data->file) === '') {
            throw new \InvalidArgumentException('File entry is empty');
        }

        $storage = $this->getSourcesStorage($data);
        $result = new SourceMapping($storage->getSource());

        $ctx = new Context();
        foreach ($this->readMapping($data->mappings) as $line => $section) {
            $ctx->column = 0;

            foreach ($section as $segment) {
                $size = \count($segment);

                $entry = match ($size) {
                    1 => new Entry\UnmappedEntry(
                        line: $line,
                        column: $ctx->column += $segment[0],
                    ),
                    4 => new Entry\Entry(
                        line: $line,
                        column: $ctx->column += $segment[0],
                        source: $storage[$ctx->sourceFileIndex += $segment[1]]
                            ?? throw $this->invalidSourceFile($ctx->sourceFileIndex),
                        sourceLine: $ctx->sourceLine += $segment[2],
                        sourceColumn: $ctx->sourceColumn += $segment[3],
                    ),
                    5 => new Entry\NamedEntry(
                        line: $line,
                        column: $ctx->column += $segment[0],
                        source: $storage[$ctx->sourceFileIndex += $segment[1]]
                            ?? throw $this->invalidSourceFile($ctx->sourceFileIndex),
                        sourceLine: $ctx->sourceLine += $segment[2],
                        sourceColumn: $ctx->sourceColumn += $segment[3],
                        name: $data->names[$ctx->sourceName += $segment[4]]
                            ?? throw $this->invalidNameIndex($ctx->sourceName),
                    ),
                    default => throw new \InvalidArgumentException(
                        'Unexpected number of values for entry: ' . $size,
                    )
                };

                $result->add($entry);
            }
        }

        return $result;
    }

    /**
     * @param int $index
     * @return \OutOfBoundsException
     */
    private function invalidSourceFile(int $index): \OutOfBoundsException
    {
        return new \OutOfBoundsException(
            'Unexpected index of source file for entry: ' . $index
        );
    }

    /**
     * @param int $index
     * @return \OutOfBoundsException
     */
    private function invalidNameIndex(int $index): \OutOfBoundsException
    {
        return new \OutOfBoundsException(
            'Unexpected index of name for entry: ' . $index
        );
    }

    /**
     * @param string $mapping
     * @return iterable<array<int>>
     * @psalm-suppress InvalidReturnType
     */
    private function readMapping(string $mapping): iterable
    {
        foreach (\explode(';', $mapping) as $index => $segment) {
            $result = [];

            if ($segment === '') {
                continue;
            }

            foreach (\explode(',', $segment) as $chunk) {
                $result[] = $this->codec->decode($chunk);
            }

            yield $index => $result;
        }
    }

    /**
     * @param SourceMapObject $data
     * @return SourcesStorage
     */
    private function getSourcesStorage(SourceMapObject $data): SourcesStorage
    {
        $sources = [];

        foreach ($data->sources as $i => $name) {
            $sources[] = $this->sources->fromSource($data->sourcesContent[$i], $name);
        }

        return new SourcesStorage(
            $this->getSource($data->file, $data->sourceRoot),
            $sources
        );
    }

    /**
     * @param string|null $file
     * @param string|null $root
     * @return ReadableInterface
     */
    private function getSource(?string $file, ?string $root): ReadableInterface
    {
        if ($file === null) {
            return $this->sources->fromSource();
        }

        if ($root !== null) {
            $file = \rtrim($root, '\\/') . '/' . \ltrim($file, '\\/');
        }

        return $file && \is_file($file)
            ? $this->sources->fromPathname($file)
            : $this->sources->fromSource('', $file ?: null)
        ;
    }
}
