<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Exception\GrammarNotFoundException;
use Phplrt\Compiler\Exception\IncludeException;
use Phplrt\Compiler\Exception\UnsupportedFormatException;
use Phplrt\Compiler\Exception\UnsupportedPragmaException;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class CompilerTest extends TestCase
{
    public function testReferencesAreRead(): void
    {
        $compiler = $this->load('grammar.pp2');

        $tokens = [];

        foreach ($compiler->lexer->tokens as $token) {
            $tokens[] = $token->name;
        }

        Assert::same($tokens, ['T_NUMBER', 'T_PLUS', 'T_WHITESPACE']);
        Assert::same($compiler->parser->initial?->printReference(), 'Expression');
    }

    public function testReferencesAreReadInPlace(): void
    {
        $compiler = $this->load('grammar.pp2');

        $result = $compiler->parser
            ->build($compiler->lexer->build())
            ->toParser($compiler->lexer->build()->toLexer())
            ->parse(StringSource::createFromString('1 + 2 + 39'));

        Assert::same($result, 42);
    }

    public function testPPGrammarIsNotSupported(): void
    {
        Expect::exception(UnsupportedFormatException::class)
        ->withMessage('Grammar files written in the "pp" format are not supported');

        $this->load('legacy.pp');
    }

    public function testUnresolvableReferenceIsReported(): void
    {
        Expect::exception(GrammarNotFoundException::class)
        ->withMessageContaining('nowhere/at/all: failed to open stream');

        $this->load('unresolvable.pp2');
    }

    public function testErrorOfAReferredGrammarIsReported(): void
    {
        try {
            $this->load('broken.pp2');
        } catch (IncludeException $e) {
            Assert::same($e->getMessage(), 'An error occurred while loading "nested/broken" grammar');
            Assert::same($e->offset, 0);
            Assert::same($e->length, 22);
            Assert::instanceOf($e->getPrevious(), UnsupportedPragmaException::class);

            return;
        }

        Assert::fail('The grammar has been read');
    }

    public function testGrammarOfNoFileIsRead(): void
    {
        $compiler = new Compiler();
        $compiler->load(StringSource::createFromString('%token T_NUMBER \d++'));

        Assert::same($compiler->lexer->tokens[0]->name, 'T_NUMBER');
    }

    private function load(string $name): Compiler
    {
        $compiler = new Compiler();
        $compiler->load(FileSource::createFromPathname(__DIR__ . '/resources/' . $name));

        return $compiler;
    }
}
