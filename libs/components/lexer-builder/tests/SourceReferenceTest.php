<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer-compiler')]
#[Test]
final class SourceReferenceTest extends TestCase
{
    private const string SOURCE = "%token T_NAME [a-z]++\n%token T_END \" -> default\n";

    public function testDefinitionRefersToTheSource(): void
    {
        $source = StringSource::createFromString(self::SOURCE);

        $lexer = new LexerBuilder();
        $definition = $lexer->addValue('"', 'T_END');
        $definition->setSource($source, 22, 12);

        Assert::same($definition->context?->source, $source);
        Assert::same($definition->context?->offset, 22);
        Assert::same($definition->context?->length, 12);
    }

    public function testErrorUnderlinesTheDefinition(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addValue('"', 'T_END')
            ->exit()
            ->setSource(StringSource::createFromString(self::SOURCE), 22, 12);

        try {
            $lexer->build();
        } catch (CompilationFailedException $e) {
            Assert::string((string) $e)->contains('  | ^^^^^^^^^^^^');

            return;
        }

        Assert::fail('A lexer ending the reading of nobody is expected to be reported');
    }

    public function testErrorRefersToTheSourceOfTheDefinition(): void
    {
        $source = StringSource::createFromString(self::SOURCE);

        $lexer = new LexerBuilder();
        $lexer->addValue('"', 'T_END')
            ->exit()
            ->setSource($source, 22);

        try {
            $lexer->build();
        } catch (CompilationFailedException $e) {
            Assert::same($e->context?->source, $source);
            Assert::string((string) $e)->contains('2 | %token T_END " -> default');

            return;
        }

        Assert::fail('A lexer ending the reading of nobody is expected to be reported');
    }

    public function testErrorRefersToTheFileOfTheDefinition(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addValue('"', 'T_END')
            ->exit()
            ->setSource(VirtualSource::createFromString('/app/example.pp2', self::SOURCE), 22);

        try {
            $lexer->build();
        } catch (CompilationFailedException $e) {
            Assert::string((string) $e)->contains('--> /app/example.pp2:2:1');

            return;
        }

        Assert::fail('A lexer ending the reading of nobody is expected to be reported');
    }

    public function testErrorWithoutTheSource(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addValue('"', 'T_END')->exit();

        try {
            $lexer->build();
        } catch (CompilationFailedException $e) {
            Assert::null($e->context);
            Assert::true(\str_starts_with((string) $e, 'error[CompilationFailedException]: '));

            return;
        }

        Assert::fail('A lexer ending the reading of nobody is expected to be reported');
    }
}
