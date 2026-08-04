<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Exception\GrammarNotFoundException;
use Phplrt\Compiler\Exception\IncludeException;
use Phplrt\Compiler\Exception\UnsupportedFormatException;
use Phplrt\Compiler\Exception\UnsupportedPragmaException;
use Phplrt\Source\File;
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/compiler')]
final class CompilerTest extends TestCase
{
    #[TestDox('A grammar is read along with every grammar it refers to')]
    public function testReferencesAreRead(): void
    {
        $compiler = $this->load('grammar.pp2');

        $tokens = [];

        foreach ($compiler->lexer->tokens as $token) {
            $tokens[] = $token->name;
        }

        // The lexemes are referred to from two grammars at once and are read
        // exactly once
        self::assertSame(['T_NUMBER', 'T_PLUS', 'T_WHITESPACE'], $tokens);
        self::assertSame('Expression', $compiler->parser->initial?->printReference());
    }

    #[TestDox('The declarations of a grammar are read where it is referred to')]
    public function testReferencesAreReadInPlace(): void
    {
        $compiler = $this->load('grammar.pp2');

        $result = $compiler->parser
            ->build($compiler->lexer->build())
            ->toParser($compiler->lexer->build()->toLexer())
            ->parse(new Source('1 + 2 + 39'));

        self::assertSame(42, $result);
    }

    #[TestDox('A grammar written in the PP format is reported')]
    public function testPPGrammarIsNotSupported(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessageIs('Grammar files written in the "pp" format are not supported');

        $this->load('legacy.pp');
    }

    #[TestDox('A reference pointing at no file is reported')]
    public function testUnresolvableReferenceIsReported(): void
    {
        $this->expectException(GrammarNotFoundException::class);
        $this->expectExceptionMessageIsOrContains('nowhere/at/all: failed to open stream');

        $this->load('unresolvable.pp2');
    }

    #[TestDox('An error of a referred grammar is reported along with the reference')]
    public function testErrorOfAReferredGrammarIsReported(): void
    {
        try {
            $this->load('broken.pp2');
        } catch (IncludeException $e) {
            self::assertSame('An error occurred while loading "nested/broken" grammar', $e->getMessage());
            self::assertSame(0, $e->offset);
            self::assertSame(22, $e->length);
            self::assertInstanceOf(UnsupportedPragmaException::class, $e->getPrevious());

            return;
        }

        self::fail('The grammar has been read');
    }

    #[TestDox('A grammar written in no file is read as well')]
    public function testGrammarOfNoFileIsRead(): void
    {
        $compiler = new Compiler();
        $compiler->load(new Source('%token T_NUMBER \d++'));

        self::assertSame('T_NUMBER', $compiler->lexer->tokens[0]->name);
    }

    /**
     * @param non-empty-string $name
     */
    private function load(string $name): Compiler
    {
        $compiler = new Compiler();
        $compiler->load(new File(__DIR__ . '/resources/' . $name));

        return $compiler;
    }
}
