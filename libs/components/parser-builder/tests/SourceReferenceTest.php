<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser-compiler')]
#[Test]
final class SourceReferenceTest extends TestCase
{
    private const string SOURCE = "%token T_NUMBER \\d++\n\nRoot : ;\n";

    private const int RULE_OFFSET = 22;

    public function testDefinitionRefersToTheSource(): void
    {
        $source = StringSource::createFromString(self::SOURCE);

        $parser = new ParserBuilder();
        $definition = $parser->addConcatenation([], 'Root');
        $definition->setSource($source, self::RULE_OFFSET);

        Assert::same($definition->context?->source, $source);
        Assert::same($definition->context?->offset, self::RULE_OFFSET);
    }

    public function testErrorRefersToTheSourceOfTheRule(): void
    {
        $source = StringSource::createFromString(self::SOURCE);

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([], 'Root')
            ->setSource($source, self::RULE_OFFSET));

        try {
            $parser->build(self::createLexerBuilder()->build());
        } catch (CompilationFailedException $e) {
            Assert::same($e->context?->source, $source);
            Assert::string((string) $e)->contains('3 | Root : ;');

            return;
        }

        Assert::fail('A rule referring to nothing is expected to be reported');
    }

    public function testErrorRefersToTheFileOfTheRule(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([], 'Root')
            ->setSource(VirtualSource::createFromString('/app/example.pp2', self::SOURCE), self::RULE_OFFSET));

        try {
            $parser->build(self::createLexerBuilder()->build());
        } catch (CompilationFailedException $e) {
            Assert::string((string) $e)->contains('--> /app/example.pp2:3:1');

            return;
        }

        Assert::fail('A rule referring to nothing is expected to be reported');
    }

    public function testErrorWithoutTheSource(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([], 'Root'));

        try {
            $parser->build(self::createLexerBuilder()->build());
        } catch (CompilationFailedException $e) {
            Assert::null($e->context);
            Assert::true(\str_starts_with((string) $e, 'error[CompilationFailedException]: '));

            return;
        }

        Assert::fail('A rule referring to nothing is expected to be reported');
    }

    public function testErrorRefersToTheSourceOfTheReducer(): void
    {
        $code = "Root -> { return \$children }\n  : <T_NUMBER>\n  ;\n";
        $source = StringSource::createFromString($code);

        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root')->setReducer(new PhpCodeReducer('return $children')->setSource($source, 10)));

        try {
            $parser->build($lexer->build())
                ->toParser(self::createLexer($lexer));
        } catch (ParserCompilerException $e) {
            Assert::same($e->context?->source, $source);
            Assert::string((string) $e)->contains('1 | Root -> { return $children }');

            return;
        }

        Assert::fail('A reducer that cannot be compiled is expected to be reported');
    }
}
