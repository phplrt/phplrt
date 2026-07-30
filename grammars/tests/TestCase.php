<?php

declare(strict_types=1);

namespace Phplrt\Example\Tests;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\File;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * @phpstan-type LanguageRawIndexType list<array{
 *     name: non-empty-string,
 *     entry: non-empty-string,
 *     example: non-empty-list<non-empty-string>,
 * }>
 */
abstract class TestCase extends BaseTestCase
{
    protected const string ROOT_DIRECTORY = __DIR__ . '/..';

    protected const string GRAMMARS_INDEX_PATHNAME = self::ROOT_DIRECTORY . '/grammars.json';

    /**
     * @param non-empty-string $pathname
     */
    private static function createSource(string $pathname): FileInterface
    {
        return new File(self::ROOT_DIRECTORY . '/' . $pathname);
    }

    /**
     * @return LanguageRawIndexType
     * @throws \JsonException
     */
    private static function read(): array
    {
        $json = @\file_get_contents(self::GRAMMARS_INDEX_PATHNAME);

        if ($json === false || $json === '') {
            throw new \RuntimeException('Could not read ' . self::GRAMMARS_INDEX_PATHNAME);
        }

        /** @var LanguageRawIndexType */
        return \json_decode($json, true, 4, JSON_THROW_ON_ERROR);
    }

    /**
     * @return iterable<non-empty-string, array{FileInterface, list<FileInterface>}>
     * @throws \JsonException
     */
    public static function grammarDataProvider(): iterable
    {
        foreach (self::read() as $index) {
            yield $index['name'] => [
                self::createSource($index['entry']),
                \array_map(self::createSource(...), $index['example']),
            ];
        }
    }
}
