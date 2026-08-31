<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Syntax\PP2\PP2Parser;
use Phplrt\Compiler\Syntax\PP3\PP3Parser;
use Phplrt\Source\FileSource;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class SyntaxGrammarTest extends TestCase
{
    private const string BUILD_SCRIPT = 'composer dev:syntax';

    public static function formatsDataProvider(): iterable
    {
        yield 'pp2' => [
            __DIR__ . '/../resources/pp2.pp3',
            __DIR__ . '/../src/Syntax/PP2/PP2Parser.php',
            'Phplrt\\Compiler\\Syntax\\PP2',
            'PP2Parser',
        ];

        yield 'pp3' => [
            __DIR__ . '/../resources/pp3.pp3',
            __DIR__ . '/../src/Syntax/PP3/PP3Parser.php',
            'Phplrt\\Compiler\\Syntax\\PP3',
            'PP3Parser',
        ];
    }

    #[DataProvider('formatsDataProvider')]
    public function testGeneratedParserMatchesTheGrammar(
        string $grammar,
        string $pathname,
        string $namespace,
        string $class,
    ): void {
        $expected = (string) (new Compiler())
            ->load(FileSource::createFromPathname($grammar))
            ->generate()
            ->withNamespaceName($namespace)
            ->withClassName($class);

        Assert::same($expected, \file_get_contents($pathname), \sprintf('The grammar has changed, run "php %s"', self::BUILD_SCRIPT));
    }

    public function testPP3GrammarIsReadByItsOwnParser(): void
    {
        $declarations = (new PP3Parser())
            ->parse(FileSource::createFromPathname(__DIR__ . '/../resources/pp3.pp3'));

        Assert::notBlank([...$declarations]);
    }

    public function testPP2GrammarIsReadByItsOwnParser(): void
    {
        $declarations = (new PP2Parser())
            ->parse(FileSource::createFromPathname(__DIR__ . '/resources/grammar.pp2'));

        Assert::notBlank([...$declarations]);
    }
}
