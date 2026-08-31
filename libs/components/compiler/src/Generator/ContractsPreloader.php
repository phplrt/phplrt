<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

use Composer\InstalledVersions;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Finds the contracts a generated parser is written against.
 */
final class ContractsPreloader
{
    /**
     * The packages the runtime of a generated parser is written against, each
     * named along with a symbol of its own.
     *
     * @var non-empty-array<non-empty-string, class-string>
     */
    private const array PACKAGES = [
        'phplrt/source-contracts' => ReadableInterface::class,
        'phplrt/lexer-contracts' => LexerInterface::class,
        'phplrt/parser-contracts' => ParserInterface::class,
    ];

    /**
     * The file the symbols of a package are listed in, in the order their
     * declarations depend on each other.
     *
     * @var non-empty-string
     */
    private const string PRELOAD_FILENAME = 'preload.php';

    /**
     * @var list<SymbolInclude>|null
     */
    private ?array $includes = null;

    /**
     * Returns every contract of the runtime, in the order their declarations
     * depend on each other.
     *
     * @return list<SymbolInclude>
     */
    public function createIncludes(): array
    {
        return $this->includes ??= self::findIncludes();
    }

    /**
     * @return list<SymbolInclude>
     */
    private static function findIncludes(): array
    {
        $result = [];

        foreach (self::PACKAGES as $package => $symbol) {
            foreach (self::readSymbols($package, $symbol) as $contract) {
                $result[] = new SymbolInclude($contract, SymbolType::createFromSymbol($contract));
            }
        }

        return $result;
    }

    /**
     * Returns the names of the symbols the given package declares, or an empty
     * list in case of the package cannot be found.
     *
     * @param non-empty-string $package
     * @param class-string $symbol
     * @return list<non-empty-string>
     */
    private static function readSymbols(string $package, string $symbol): array
    {
        $pathname = self::findPreloadPathname($package, $symbol);

        if ($pathname === null) {
            return [];
        }

        /** @var list<non-empty-string> */
        return require $pathname;
    }

    /**
     * @param non-empty-string $package
     * @param class-string $symbol
     * @return non-empty-string|null
     */
    private static function findPreloadPathname(string $package, string $symbol): ?string
    {
        $directory = self::findPackageDirectory($package);

        if ($directory !== null) {
            $pathname = $directory . '/src/' . self::PRELOAD_FILENAME;

            if (\is_file($pathname)) {
                return $pathname;
            }
        }

        // A package the root one replaces is installed by nothing, so it is
        // only reachable through a symbol it declares.
        $declaration = (new \ReflectionClass($symbol))
            ->getFileName();

        if ($declaration === false) {
            return null;
        }

        $pathname = \dirname($declaration) . '/' . self::PRELOAD_FILENAME;

        return \is_file($pathname) ? $pathname : null;
    }

    /**
     * @param non-empty-string $package
     * @return non-empty-string|null
     */
    private static function findPackageDirectory(string $package): ?string
    {
        if (!\class_exists(InstalledVersions::class) || !InstalledVersions::isInstalled($package)) {
            return null;
        }

        $directory = InstalledVersions::getInstallPath($package);

        return $directory === null || $directory === '' ? null : $directory;
    }
}
