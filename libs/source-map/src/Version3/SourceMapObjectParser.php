<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Version3;

use Phplrt\SourceMap\Format;
use Phplrt\SourceMap\GeneratorV3;
use Phplrt\SourceMap\ParserV3;

/**
 * @psalm-import-type SourceMapV3Input from ParserV3
 * @psalm-import-type SourceMapV3Output from GeneratorV3
 *
 * @see ParserV3
 * @see GeneratorV3
 */
class SourceMapObjectParser
{
    /**
     * @var positive-int|0
     */
    private const JSON_DECODING_FLAGS = \JSON_OBJECT_AS_ARRAY;

    /**
     * @param string $contents
     * @return SourceMapObject
     * @throws \JsonException
     *
     * @psalm-suppress ArgumentTypeCoercion
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function parse(string $contents): SourceMapObject
    {
        $data = $this->fromJson($contents);

        return new SourceMapObject(
            version: $this->parseInt($data, 'version'),
            file: $this->parseStringOrNull($data, 'file'),
            lineCount: $this->parseIntOrNull($data, 'lineCount'),
            sourceRoot: $this->parseStringOrNull($data, 'sourceRoot'),
            sources: $this->parseArrayOf($data, 'sources', 'string'),
            sourcesContent: $this->parseArrayOf($data, 'sourcesContent', 'string'),
            names: $this->parseArrayOf($data, 'names', 'string'),
            mappings: $this->parseString($data, 'mappings'),
        );
    }

    /**
     * Strip any JSON XSSI avoidance prefix from the string (as documented
     * in the source maps specification), and then parse the string as
     * JSON.
     *
     * @param string $contents
     * @return array
     * @throws \JsonException
     */
    private function fromJson(string $contents): array
    {
        $contents = \preg_replace('/^\)]}\'[^\n]*\n/', '', $contents);

        return (array)\json_decode($contents, flags: self::JSON_DECODING_FLAGS | \JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $message
     * @param array $args
     * @return never
     */
    private function error(string $message, array $args = []): never
    {
        $map = static fn(mixed $arg): string => (string)(\is_scalar($arg) ? $arg : \get_debug_type($arg));
        $message = \vsprintf($message, \array_map($map, $args));

        throw new \InvalidArgumentException(
            \sprintf('%s format error: %s', Format::V3->getName(), $message)
        );
    }

    /**
     * @param SourceMapV3Input $data
     * @param non-empty-string $section
     * @return int
     */
    private function parseInt(array $data, string $section): int
    {
        if (!isset($data[$section])) {
            $this->error('The "%s" source map section is required', [$section]);
        }

        if (!\is_int($data[$section])) {
            $this->error('The "%s" section must contain int value, but "%s" passed', [
                $section,
                $data[$section]
            ]);
        }

        return $data[$section];
    }

    /**
     * @param array $data
     * @param non-empty-string $section
     * @return string|null
     */
    private function parseStringOrNull(array $data, string $section): ?string
    {
        if (isset($data[$section]) && !\is_string($data[$section])) {
            $this->error('The "%s" section must contain string value, but "%s" passed', [
                $section,
                $data[$section]
            ]);
        }

        return $data[$section] ?? null;
    }

    /**
     * @param array $data
     * @param non-empty-string $section
     * @return int|null
     */
    private function parseIntOrNull(array $data, string $section): ?int
    {
        if (isset($data[$section]) && !\is_int($data[$section])) {
            $this->error('The "%s" section must contain int value, but "%s" passed', [
                $section,
                $data[$section]
            ]);
        }

        return $data[$section] ?? null;
    }

    /**
     * @param array $data
     * @param non-empty-string $section
     * @param string $of
     * @return array
     */
    private function parseArrayOf(array $data, string $section, string $of): array
    {
        if (!isset($data[$section])) {
            $this->error('The "%s" source map section is required', [$section]);
        }

        if (!\is_array($data[$section])) {
            $this->error('The "%s" section must contain array<%s> value, but "%s" passed', [
                $section,
                $of,
                $data[$section]
            ]);
        }

        return $data[$section];
    }

    /**
     * @param SourceMapV3Input $data
     * @param non-empty-string $section
     * @return string
     */
    private function parseString(array $data, string $section): string
    {
        if (!isset($data[$section])) {
            $this->error('The "%s" source map section is required', [$section]);
        }

        if (!\is_string($data[$section])) {
            $this->error('The "%s" section must contain string value, but "%s" passed', [
                $section,
                $data[$section]
            ]);
        }

        return $data[$section];
    }
}
