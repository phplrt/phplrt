<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Generator\ContractsPreloader;
use Phplrt\Compiler\Generator\GeneratedOutput;
use Phplrt\Compiler\Generator\SymbolInclude;
use Phplrt\Compiler\Generator\SymbolType;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\UserDefinedChannel;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/compiler')]
final class ContractsPreloadTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string GRAMMAR_PATHNAME = __DIR__ . '/resources/grammar.pp2';

    /**
     * @var list<non-empty-string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $pathname) {
            @\unlink($pathname);
        }

        $this->files = [];
    }

    #[TestDox('Every contract of the runtime is loaded by the generated code')]
    public function testRuntimeContractsAreLoaded(): void
    {
        $code = (string) self::generate();
        $includes = self::createIncludes();

        self::assertNotEmpty($includes);

        foreach ($includes as $include) {
            self::assertStringContainsString(\sprintf(
                "\\%s_exists(\\%s::class);\n",
                $include->type->value,
                $include->symbol,
            ), $code);
        }
    }

    #[TestDox('Only the contracts of the source, lexer and parser are loaded')]
    public function testOnlyRuntimeContractsAreLoaded(): void
    {
        foreach (self::createIncludes() as $include) {
            self::assertMatchesRegularExpression(
                '/^Phplrt\\\\Contracts\\\\(Source|Lexer|Parser)\\\\/',
                $include->symbol,
            );
        }
    }

    #[TestDox('A contract is loaded by the declaration it is declared by')]
    public function testContractIsLoadedByItsDeclaration(): void
    {
        $code = (string) self::generate();

        self::assertStringContainsString(
            \sprintf('\\interface_exists(\\%s::class);', ReadableInterface::class),
            $code,
        );

        self::assertStringContainsString(
            \sprintf('\\enum_exists(\\%s::class);', Channel::class),
            $code,
        );

        self::assertStringContainsString(
            \sprintf('\\class_exists(\\%s::class);', UserDefinedChannel::class),
            $code,
        );
    }

    #[TestDox('Nothing is included by the generated code')]
    public function testNothingIsIncluded(): void
    {
        $code = (string) self::generate();

        self::assertStringNotContainsString('require', $code);
        self::assertStringNotContainsString('include', $code);
    }

    #[TestDox('The declaration a contract is declared by is told apart')]
    public function testDeclarationOfAContractIsToldApart(): void
    {
        $types = [];

        foreach (self::createIncludes() as $include) {
            $types[$include->symbol] = $include->type;
        }

        self::assertSame(SymbolType::InterfaceType, $types[LexerInterface::class] ?? null);
        self::assertSame(SymbolType::EnumType, $types[Channel::class] ?? null);
        self::assertSame(SymbolType::ClassType, $types[UserDefinedChannel::class] ?? null);
    }

    #[TestDox('The contracts are loaded after the strict types are declared')]
    public function testContractsAreLoadedAfterTheDeclaration(): void
    {
        $code = (string) self::generate();

        self::assertGreaterThan(
            \strpos($code, 'declare(strict_types=1);'),
            \strpos($code, 'interface_exists('),
        );
    }

    #[TestDox('The contracts are loaded after the namespace the parser belongs to')]
    public function testContractsAreLoadedAfterTheNamespace(): void
    {
        $code = (string) self::generate()
            ->withNamespaceName('Example\\Some');

        self::assertGreaterThan(
            \strpos($code, 'namespace Example\\Some;'),
            \strpos($code, 'interface_exists('),
        );
    }

    #[TestDox('The contracts are loaded in the order their declarations depend on each other')]
    public function testContractsAreLoadedInDependencyOrder(): void
    {
        $symbols = [];

        foreach (self::createIncludes() as $include) {
            $symbols[] = $include->symbol;
        }

        $loaded = [];

        foreach ($symbols as $symbol) {
            foreach (self::findDependenciesOf($symbol) as $dependency) {
                if (!\in_array($dependency, $symbols, true)) {
                    continue;
                }

                self::assertContains($dependency, $loaded, \sprintf(
                    'The "%s" contract must be preceded by the "%s" contract it depends on',
                    $symbol,
                    $dependency,
                ));
            }

            $loaded[] = $symbol;
        }

        self::assertSame($symbols, $loaded);
    }

    #[TestDox('A parser generated without the preloading leaves the contracts to be loaded on demand')]
    public function testContractsPreloadingIsDisabled(): void
    {
        $code = (string) self::generate()
            ->withoutContractsPreloading();

        self::assertStringNotContainsString('interface_exists(', $code);
        self::assertStringNotContainsString('enum_exists(', $code);
        self::assertStringNotContainsString('class_exists(', $code);
    }

    #[TestDox('A parser generated without the preloading is still written the way it is asked for')]
    public function testContractsPreloadingIsDisabledAlongWithTheOtherOptions(): void
    {
        $code = (string) self::generate()
            ->withoutContractsPreloading()
            ->withNamespaceName('Example\\Some')
            ->withClassImport('App\\Node')
            ->withClassName('SomeParser');

        self::assertStringContainsString("\nnamespace Example\\Some;\n", $code);
        self::assertStringContainsString("\nuse App\\Node;\n", $code);
        self::assertStringContainsString("\nreadonly class SomeParser extends", $code);
        self::assertStringNotContainsString('interface_exists(', $code);
    }

    #[TestDox('The parser loading the contracts recognizes what the grammar says')]
    public function testParserLoadingTheContractsIsRead(): void
    {
        $pathname = $this->createPathname();

        self::generate()->save($pathname);

        $parser = require $pathname;

        self::assertInstanceOf(ParserInterface::class, $parser);
        self::assertSame(42, $parser->parse(StringSource::createFromString('1 + 2 + 39')));
    }

    /**
     * @return list<SymbolInclude>
     */
    private static function createIncludes(): array
    {
        return new ContractsPreloader()->createIncludes();
    }

    /**
     * @param non-empty-string $symbol
     * @return list<non-empty-string>
     */
    private static function findDependenciesOf(string $symbol): array
    {
        return [
            ...\array_keys((array) \class_parents($symbol)),
            ...\array_keys((array) \class_implements($symbol)),
        ];
    }

    private static function generate(): GeneratedOutput
    {
        return new Compiler()
            ->load(FileSource::createFromPathname(self::GRAMMAR_PATHNAME))
            ->generate();
    }

    /**
     * @return non-empty-string
     */
    private function createPathname(): string
    {
        $pathname = \sys_get_temp_dir() . '/phplrt-' . \bin2hex(\random_bytes(8)) . '.php';

        $this->files[] = $pathname;

        return $pathname;
    }
}
