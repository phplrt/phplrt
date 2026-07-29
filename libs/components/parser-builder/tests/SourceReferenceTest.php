<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Source\Source;
use Phplrt\Source\VirtualFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser-compiler')]
final class SourceReferenceTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string SOURCE = "%token T_NUMBER \\d++\n\nRoot : ;\n";

    /**
     * The offset the "Root" rule is written at.
     *
     * @var int<0, max>
     */
    private const int RULE_OFFSET = 22;

    #[TestDox('A definition refers to the source it has been written in')]
    public function testDefinitionRefersToTheSource(): void
    {
        $source = new Source(self::SOURCE);

        $parser = new ParserBuilder();
        $definition = $parser->addConcatenation([], 'Root');
        $definition->setSource($source, self::RULE_OFFSET);

        self::assertSame($source, $definition->context?->source);
        self::assertSame(self::RULE_OFFSET, $definition->context?->offset);
    }

    #[TestDox('An error is printed along with the fragment of the source the rule has been written in')]
    public function testErrorRefersToTheSourceOfTheRule(): void
    {
        $source = new Source(self::SOURCE);

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([], 'Root')
            ->setSource($source, self::RULE_OFFSET));

        try {
            $parser->build(self::createLexerBuilder()->build());
        } catch (CompilationFailedException $e) {
            self::assertSame($source, $e->context?->source);
            self::assertStringContainsString('3 | Root : ;', (string) $e);

            return;
        }

        self::fail('A rule referring to nothing is expected to be reported');
    }

    #[TestDox('The name of the file the rule has been written in is printed as well')]
    public function testErrorRefersToTheFileOfTheRule(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([], 'Root')
            ->setSource(new VirtualFile('/app/example.pp2', self::SOURCE), self::RULE_OFFSET));

        try {
            $parser->build(self::createLexerBuilder()->build());
        } catch (CompilationFailedException $e) {
            self::assertStringContainsString('--> /app/example.pp2:3:1', (string) $e);

            return;
        }

        self::fail('A rule referring to nothing is expected to be reported');
    }

    #[TestDox('An error of a rule built by hand is printed as an ordinary exception')]
    public function testErrorWithoutTheSource(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([], 'Root'));

        try {
            $parser->build(self::createLexerBuilder()->build());
        } catch (CompilationFailedException $e) {
            self::assertNull($e->context);
            self::assertStringStartsWith(CompilationFailedException::class, (string) $e);

            return;
        }

        self::fail('A rule referring to nothing is expected to be reported');
    }

    #[TestDox('An error of a reducer is printed along with the code it has been written of')]
    public function testErrorRefersToTheSourceOfTheReducer(): void
    {
        $code = "Root -> { return \$children }\n  : <T_NUMBER>\n  ;\n";
        $source = new Source($code);

        $lexer = self::createLexerBuilder();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root')->setReducer(new PhpCodeReducer('return $children')->setSource($source, 10)));

        try {
            $parser->build($lexer->build())
                ->toParser(self::createLexer($lexer));
        } catch (ParserCompilerException $e) {
            self::assertSame($source, $e->context?->source);
            self::assertStringContainsString('1 | Root -> { return $children }', (string) $e);

            return;
        }

        self::fail('A reducer that cannot be compiled is expected to be reported');
    }
}
