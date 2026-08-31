<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Generator\ContractsPreloader;
use Phplrt\Compiler\Generator\GeneratedOutput;
use Phplrt\Compiler\Generator\SymbolType;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\UserDefinedChannel;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Lifecycle\AfterTest;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class ContractsPreloadTest extends TestCase
{
    private const GRAMMAR_PATHNAME = __DIR__ . '/resources/grammar.pp2';

    private array $files = [];

    #[AfterTest]
    protected function tearDown(): void
    {
        foreach ($this->files as $pathname) {
            @\unlink($pathname);
        }

        $this->files = [];
    }

    public function testRuntimeContractsAreLoaded(): void
    {
        $code = (string) self::generate();
        $includes = self::createIncludes();

        Assert::notBlank($includes);

        foreach ($includes as $include) {
            Assert::string($code)->contains(\sprintf(
                "\\%s_exists(\\%s::class);\n",
                $include->type->value,
                $include->symbol,
            ));
        }
    }

    public function testOnlyRuntimeContractsAreLoaded(): void
    {
        foreach (self::createIncludes() as $include) {
            Assert::true((bool) \preg_match('/^Phplrt\\\\Contracts\\\\(Source|Lexer|Parser)\\\\/', $include->symbol));
        }
    }

    public function testContractIsLoadedByItsDeclaration(): void
    {
        $code = (string) self::generate();

        Assert::string($code)
            ->contains(\sprintf('\\interface_exists(\\%s::class);', ReadableInterface::class))
            ->contains(\sprintf('\\enum_exists(\\%s::class);', Channel::class))
            ->contains(\sprintf('\\class_exists(\\%s::class);', UserDefinedChannel::class));
    }

    public function testNothingIsIncluded(): void
    {
        $code = (string) self::generate();

        Assert::string($code)
            ->notContains('require')
            ->notContains('include');
    }

    public function testDeclarationOfAContractIsToldApart(): void
    {
        $types = [];

        foreach (self::createIncludes() as $include) {
            $types[$include->symbol] = $include->type;
        }

        Assert::same($types[LexerInterface::class] ?? null, SymbolType::InterfaceType);
        Assert::same($types[Channel::class] ?? null, SymbolType::EnumType);
        Assert::same($types[UserDefinedChannel::class] ?? null, SymbolType::ClassType);
    }

    public function testContractsAreLoadedAfterTheDeclaration(): void
    {
        $code = (string) self::generate();

        Assert::numeric(\strpos($code, 'interface_exists('))->greaterThan(\strpos($code, 'declare(strict_types=1);'));
    }

    public function testContractsAreLoadedAfterTheNamespace(): void
    {
        $code = (string) self::generate()
            ->withNamespaceName('Example\\Some');

        Assert::numeric(\strpos($code, 'interface_exists('))->greaterThan(\strpos($code, 'namespace Example\\Some;'));
    }

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

                Assert::contains($loaded, $dependency, \sprintf(
                    'The "%s" contract must be preceded by the "%s" contract it depends on',
                    $symbol,
                    $dependency,
                ));
            }

            $loaded[] = $symbol;
        }

        Assert::same($loaded, $symbols);
    }

    public function testContractsPreloadingIsDisabled(): void
    {
        $code = (string) self::generate()
            ->withoutContractsPreloading();

        Assert::string($code)
            ->notContains('interface_exists(')
            ->notContains('enum_exists(')
            ->notContains('class_exists(');
    }

    public function testContractsPreloadingIsDisabledAlongWithTheOtherOptions(): void
    {
        $code = (string) self::generate()
            ->withoutContractsPreloading()
            ->withNamespaceName('Example\\Some')
            ->withClassImport('App\\Node')
            ->withClassName('SomeParser');

        Assert::string($code)
            ->contains("\nnamespace Example\\Some;\n")
            ->contains("\nuse App\\Node;\n")
            ->contains("\nclass SomeParser extends")
            ->notContains('interface_exists(');
    }

    public function testParserLoadingTheContractsIsRead(): void
    {
        $pathname = $this->createPathname();

        self::generate()->save($pathname);

        $parser = require $pathname;

        Assert::instanceOf($parser, ParserInterface::class);
        Assert::same($parser->parse(StringSource::createFromString('1 + 2 + 39')), 42);
    }

    private static function createIncludes(): array
    {
        return (new ContractsPreloader())->createIncludes();
    }

    private static function findDependenciesOf(string $symbol): array
    {
        return [
            ...\array_keys((array) \class_parents($symbol)),
            ...\array_keys((array) \class_implements($symbol)),
        ];
    }

    private static function generate(): GeneratedOutput
    {
        return (new Compiler())
            ->load(FileSource::createFromPathname(self::GRAMMAR_PATHNAME))
            ->generate();
    }

    private function createPathname(): string
    {
        $pathname = \sys_get_temp_dir() . '/phplrt-' . \bin2hex(\random_bytes(8)) . '.php';

        $this->files[] = $pathname;

        return $pathname;
    }
}
