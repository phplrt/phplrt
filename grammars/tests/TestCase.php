<?php

declare(strict_types=1);

namespace Phplrt\Example\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Parser\Parser;
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

    protected const string PARSERS_DIRECTORY = __DIR__ . '/temp';

    protected const string GRAMMARS_INDEX_PATHNAME = self::ROOT_DIRECTORY . '/grammars.json';

    /**
     * @param non-empty-string $pathname
     */
    private static function createSource(string $pathname): FileInterface
    {
        return new File(self::ROOT_DIRECTORY . '/' . $pathname);
    }

    /**
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private static function createFileName(string $name): string
    {
        $result = \trim(\preg_replace('/[^a-zA-Z0-9]++/', '-', $name) ?? '', '-');

        return $result === ''
            ? \hash($name, 'xxh32')
            : $result;
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
     * @param non-empty-string $name
     * @return non-empty-string
     * @throws RuntimeExceptionInterface
     * @throws SourceExceptionInterface
     */
    private static function compile(FileInterface $grammar, string $name): string
    {
        $pathname = self::PARSERS_DIRECTORY . '/' . self::createFileName($name) . '.php';

        new Compiler()
            ->load($grammar)
            ->generate()
            ->save($pathname);

        return $pathname;
    }

    /**
     * @return iterable<non-empty-string, array{FileInterface}>
     * @throws \JsonException
     */
    public static function grammarDataProvider(): iterable
    {
        foreach (self::read() as $index) {
            yield $index['name'] => [self::createSource($index['entry'])];
        }
    }

    /**
     * @return iterable<non-empty-string, array{Parser, FileInterface}>
     * @throws RuntimeExceptionInterface
     * @throws SourceExceptionInterface
     * @throws \JsonException
     */
    public static function exampleDataProvider(): iterable
    {
        foreach (self::read() as $index) {
            $syntaxName = $index['name'];

            /** @var Parser $parser */
            $parser = require self::compile(self::createSource($index['entry']), $syntaxName);

            foreach ($index['example'] as $exampleRelativePathname) {
                $source = self::createSource($exampleRelativePathname);

                $filename = \pathinfo($source->pathname, \PATHINFO_FILENAME);

                yield $syntaxName . ': ' . $filename => [$parser, $source];
            }
        }
    }
}
