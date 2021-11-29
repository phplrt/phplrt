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
use Phplrt\SourceMap\Exception\ParsingException;
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
     * @var string
     */
    private const ERROR_VLQ_SEQ_SIZE =
        'Invalid VLQ sequence: Expected 1, 4, or 5 bytes but array(%d) { %s } received in "%s"';

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
     * @throws ParsingException
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function parse(string $contents): SourceMapping
    {
        try {
            $data = $this->parser->parse($contents);
        } catch (\Throwable $e) {
            $message = 'An error occurred while reading the JSON data of the Source Map V3: ' . $e->getMessage();
            throw new ParsingException($message, (int)$e->getCode(), $e);
        }

        if ($data->version !== 3) {
            throw new ParsingException('Unknown version: ' . $data->version);
        }

        if ($data->file !== null && \trim($data->file) === '') {
            throw new ParsingException('File entry is empty');
        }

        try {
            return $this->create($data);
        } catch (\Throwable $e) {
            $message = 'An error occurred while reading mapping data of the Source Map V3: ' . $e->getMessage();
            throw new ParsingException($message, (int)$e->getCode(), $e);
        }
    }

    /**
     * @param SourceMapObject $data
     * @return SourceMapping
     */
    private function create(SourceMapObject $data): SourceMapping
    {
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
                            ?? throw $this->invalidFileIndex($ctx->sourceFileIndex),
                        sourceLine: $ctx->sourceLine += $segment[2],
                        sourceColumn: $ctx->sourceColumn += $segment[3],
                    ),
                    5 => new Entry\NamedEntry(
                        line: $line,
                        column: $ctx->column += $segment[0],
                        source: $storage[$ctx->sourceFileIndex += $segment[1]]
                            ?? throw $this->invalidFileIndex($ctx->sourceFileIndex),
                        sourceLine: $ctx->sourceLine += $segment[2],
                        sourceColumn: $ctx->sourceColumn += $segment[3],
                        name: $data->names[$ctx->sourceName += $segment[4]]
                            ?? throw $this->invalidNameIndex($ctx->sourceName),
                    ),
                    default => throw $this->invalidSegmentSize($segment)
                };

                $result->addEntry($entry);
            }
        }

        return $result;
    }

    /**
     * @param array<int> $segment
     * @return \LengthException
     */
    private function invalidSegmentSize(array $segment): \LengthException
    {
        return new \LengthException(\vsprintf(self::ERROR_VLQ_SEQ_SIZE, [
            \count($segment),
            \implode(', ', $segment),
            $this->codec->encode($segment)
        ]));
    }

    /**
     * @param int $index
     * @return \OutOfRangeException
     */
    private function invalidFileIndex(int $index): \OutOfRangeException
    {
        return new \OutOfRangeException(\sprintf('Unexpected index #%d of the source file', $index));
    }

    /**
     * @param int $index
     * @return \OutOfRangeException
     */
    private function invalidNameIndex(int $index): \OutOfRangeException
    {
        return new \OutOfRangeException(\sprintf('Unexpected index #%d of the name for entry', $index));
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
                $result[] = [...$this->codec->decode($chunk)];
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
