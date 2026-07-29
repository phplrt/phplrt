<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Syntax\PP2\PP2Parser;
use Phplrt\Compiler\Syntax\PP3\PP3Parser;
use Phplrt\Source\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Checks that the parser reading a grammar file is still the one the grammar
 * describing that format compiles into.
 *
 * A parser cannot be built from the grammar describing its own format at
 * runtime, so it is generated ahead of time and committed. Which means the two
 * may drift apart, and this is what tells that they have.
 */
#[Group('phplrt/compiler')]
final class SyntaxGrammarTest extends TestCase
{
    /**
     * The script bringing a parser back in line with its grammar.
     *
     * @var non-empty-string
     */
    private const string BUILD_SCRIPT = 'libs/components/compiler/bin/build-syntax.php';

    /**
     * Every format the compiler reads: the grammar describing it, the file the
     * parser of it is generated into and the name it is generated under.
     *
     * @return iterable<non-empty-string, array{non-empty-string, non-empty-string, non-empty-string, non-empty-string}>
     */
    public static function formatsDataProvider(): iterable
    {
        yield 'pp2' => [
            __DIR__ . '/../resources/grammar/pp2.pp3',
            __DIR__ . '/../src/Syntax/PP2/PP2Parser.php',
            'Phplrt\\Compiler\\Syntax\\PP2',
            'PP2Parser',
        ];

        yield 'pp3' => [
            __DIR__ . '/../resources/grammar/pp3.pp3',
            __DIR__ . '/../src/Syntax/PP3/PP3Parser.php',
            'Phplrt\\Compiler\\Syntax\\PP3',
            'PP3Parser',
        ];
    }

    /**
     * @param non-empty-string $grammar
     * @param non-empty-string $pathname
     * @param non-empty-string $namespace
     * @param non-empty-string $class
     */
    #[DataProvider('formatsDataProvider')]
    #[TestDox('The parser of a format is what the grammar describing it compiles into')]
    public function testGeneratedParserMatchesTheGrammar(
        string $grammar,
        string $pathname,
        string $namespace,
        string $class,
    ): void {
        $expected = new Compiler()
            ->load(new File($grammar))
            ->generate()
            ->withNamespaceName($namespace)
            ->withClassName($class)
            ->__toString();

        self::assertSame(
            \file_get_contents($pathname),
            $expected,
            \sprintf('The grammar has changed, run "php %s"', self::BUILD_SCRIPT),
        );
    }

    #[TestDox('The grammar of the PP3 format is read by the parser it describes')]
    public function testPP3GrammarIsReadByItsOwnParser(): void
    {
        $declarations = new PP3Parser()
            ->parse(new File(__DIR__ . '/../resources/grammar/pp3.pp3'));

        self::assertNotEmpty([...$declarations]);
    }

    #[TestDox('A grammar of the PP2 format is read by the parser its own grammar describes')]
    public function testPP2GrammarIsReadByItsOwnParser(): void
    {
        $declarations = new PP2Parser()
            ->parse(new File(__DIR__ . '/resources/grammar.pp2'));

        self::assertNotEmpty([...$declarations]);
    }
}
