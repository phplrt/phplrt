<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Source\Source;
use Phplrt\Source\VirtualFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer-compiler')]
final class SourceReferenceTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string SOURCE = "%token T_NAME [a-z]++\n%token T_END \" -> default\n";

    #[TestDox('A definition refers to the source it has been written in')]
    public function testDefinitionRefersToTheSource(): void
    {
        $source = new Source(self::SOURCE);

        $lexer = new LexerBuilder();
        $definition = $lexer->addValue('"', 'T_END');
        $definition->setSource($source, 22, 12);

        self::assertSame($source, $definition->context?->source);
        self::assertSame(22, $definition->context?->offset);
        self::assertSame(12, $definition->context?->length);
    }

    #[TestDox('The whole fragment of the definition is underlined')]
    public function testErrorUnderlinesTheDefinition(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addValue('"', 'T_END')
            ->exit()
            ->setSource(new Source(self::SOURCE), 22, 12);

        try {
            $lexer->build();
        } catch (CompilationFailedException $e) {
            self::assertStringContainsString('  | ^^^^^^^^^^^^', (string) $e);

            return;
        }

        self::fail('A lexer ending the reading of nobody is expected to be reported');
    }

    #[TestDox('An error is printed along with the fragment of the source the definition has been written in')]
    public function testErrorRefersToTheSourceOfTheDefinition(): void
    {
        $source = new Source(self::SOURCE);

        $lexer = new LexerBuilder();
        $lexer->addValue('"', 'T_END')
            ->exit()
            ->setSource($source, 22);

        try {
            $lexer->build();
        } catch (CompilationFailedException $e) {
            self::assertSame($source, $e->context?->source);
            self::assertStringContainsString('2 | %token T_END " -> default', (string) $e);

            return;
        }

        self::fail('A lexer ending the reading of nobody is expected to be reported');
    }

    #[TestDox('The name of the file the definition has been written in is printed as well')]
    public function testErrorRefersToTheFileOfTheDefinition(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addValue('"', 'T_END')
            ->exit()
            ->setSource(new VirtualFile('/app/example.pp2', self::SOURCE), 22);

        try {
            $lexer->build();
        } catch (CompilationFailedException $e) {
            self::assertStringContainsString('--> /app/example.pp2:2:1', (string) $e);

            return;
        }

        self::fail('A lexer ending the reading of nobody is expected to be reported');
    }

    #[TestDox('An error of a definition built by hand is printed as an ordinary exception')]
    public function testErrorWithoutTheSource(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addValue('"', 'T_END')->exit();

        try {
            $lexer->build();
        } catch (CompilationFailedException $e) {
            self::assertNull($e->context);
            self::assertStringStartsWith(CompilationFailedException::class, (string) $e);

            return;
        }

        self::fail('A lexer ending the reading of nobody is expected to be reported');
    }
}
