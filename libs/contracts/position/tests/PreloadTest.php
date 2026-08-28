<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Position\Tests;

use Testo\Assert;
use Testo\Test;

#[Test]
final class PreloadTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string PRELOAD_PATHNAME = __DIR__ . '/../src/preload.php';

    /**
     * @var non-empty-string
     */
    private const string SOURCES_PATHNAME = __DIR__ . '/../src';

    /**
     * @var non-empty-string
     */
    private const string SYMBOL_PREFIX = 'Phplrt\\Contracts\\Position\\';

    public function testAllSymbolsExist(): void
    {
        $symbols = $this->preload();

        Assert::notBlank($symbols);

        foreach ($symbols as $symbol) {
            Assert::true(\interface_exists($symbol) || \class_exists($symbol), \sprintf('The "%s" symbol does not exist', $symbol));
        }
    }

    public function testAllSymbolsAreUnique(): void
    {
        $symbols = $this->preload();

        Assert::same($symbols, \array_values(\array_unique($symbols)));
    }

    public function testOnlyOwnSymbolsAreReturned(): void
    {
        $expected = $this->findSymbolsInSources();
        $actual = $this->preload();

        \sort($expected);
        \sort($actual);

        Assert::same($actual, $expected);
    }

    public function testEverySymbolIsPrecededByItsDependencies(): void
    {
        $symbols = $this->preload();
        $declared = [];

        foreach ($symbols as $symbol) {
            foreach ($this->findDependenciesOf($symbol) as $dependency) {
                if (!\in_array($dependency, $symbols, true)) {
                    continue;
                }

                Assert::contains($declared, $dependency, \sprintf(
                    'The "%s" symbol must be preceded by the "%s" symbol it depends on',
                    $symbol,
                    $dependency,
                ));
            }

            $declared[] = $symbol;
        }

        Assert::same($declared, $symbols);
    }

    /**
     * @return list<non-empty-string>
     */
    private function preload(): array
    {
        return require self::PRELOAD_PATHNAME;
    }

    /**
     * @param non-empty-string $symbol
     * @return list<non-empty-string>
     */
    private function findDependenciesOf(string $symbol): array
    {
        return [
            ...\array_keys((array) \class_parents($symbol)),
            ...\array_keys((array) \class_implements($symbol)),
        ];
    }

    /**
     * @return list<non-empty-string>
     */
    private function findSymbolsInSources(): array
    {
        $root = \str_replace('\\', '/', (string) \realpath(self::SOURCES_PATHNAME));
        $preload = \str_replace('\\', '/', (string) \realpath(self::PRELOAD_PATHNAME));

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );

        $result = [];

        foreach ($files as $file) {
            $pathname = \str_replace('\\', '/', $file->getPathname());

            if ($file->getExtension() !== 'php' || $pathname === $preload) {
                continue;
            }

            $relative = \substr($pathname, \strlen($root) + 1, -\strlen('.php'));

            $result[] = self::SYMBOL_PREFIX . \strtr($relative, '/', '\\');
        }

        return $result;
    }
}
