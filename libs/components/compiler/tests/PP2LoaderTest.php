<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Exception\UnsupportedPragmaException;
use Phplrt\Compiler\Exception\UnsupportedTransitionException;
use Phplrt\Compiler\Loader\GrammarReference;
use Phplrt\Compiler\Syntax\PP2\PP2Loader;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Definition\TransitionType;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleReference;
use Phplrt\Parser\Builder\Definition\TerminalRuleDefinition;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Source\VirtualFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/compiler')]
final class PP2LoaderTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string PATHNAME = '/app/grammar.pp2';

    private LexerBuilder $lexer;

    private ParserBuilder $parser;

    protected function setUp(): void
    {
        $this->lexer = new LexerBuilder();
        $this->parser = new ParserBuilder();
    }

    #[TestDox('A token declaration is added to the lexer')]
    public function testTokenIsAddedToTheLexer(): void
    {
        $this->load(<<<'PP2'
            %token T_NUMBER \d++
            %skip  T_WHITESPACE \s++
            PP2);

        [$number, $whitespace] = \array_values($this->lexer->tokens);

        self::assertSame('T_NUMBER', $number->name);
        self::assertFalse($number->isHidden);

        self::assertSame('T_WHITESPACE', $whitespace->name);
        self::assertTrue($whitespace->isHidden);
    }

    #[TestDox('A token refers to the place of the grammar it is declared in')]
    public function testTokenRefersToItsDeclaration(): void
    {
        $source = <<<'PP2'
            %skip T_WHITESPACE \s++
            %token T_NUMBER \d++
            PP2;

        $this->load($source);

        self::assertSame('%token T_NUMBER \d++', $this->readSource($source, $this->lexer->tokens[1]));
    }

    #[TestDox('A token of a named state is read by a lexer of its own')]
    public function testStateIsReadByALexerOfItsOwn(): void
    {
        $this->load(<<<'PP2'
            %token        T_QUOTE "      -> string
            %token string:T_TEXT  [^"]++
            %token string:T_END   "      -> default
            PP2);

        self::assertSame(['string'], \array_keys($this->lexer->lexers));

        $nested = $this->lexer->lexers['string'];

        self::assertInstanceOf(LexerBuilder::class, $nested);
        self::assertSame(TransitionType::Enter, $this->lexer->tokens[0]->transition?->type);
        self::assertSame('string', $this->lexer->tokens[0]->transition?->lexer);
        self::assertNull($nested->tokens[0]->transition);
        self::assertSame(TransitionType::Exit, $nested->tokens[1]->transition?->type);
    }

    #[TestDox('A token switching between two named states is reported')]
    public function testTransitionBetweenNamedStatesIsReported(): void
    {
        $this->expectException(UnsupportedTransitionException::class);
        $this->expectExceptionMessage('cannot be continued by the state "second"');

        $this->load('%token first:T_X x -> second');
    }

    #[TestDox('A pattern written inside a rule is read by an anonymous token')]
    public function testInlinePatternIsReadByAnAnonymousToken(): void
    {
        $this->load('A : "\+" B() ; B : "\+" ;');

        self::assertCount(1, $this->lexer->tokens);

        $token = $this->lexer->tokens[0];

        self::assertInstanceOf(RegexTokenDefinition::class, $token);
        self::assertNull($token->name);
        self::assertSame('\+', $token->regex);
    }

    #[TestDox('A pattern written inside a rule is never kept in the tree')]
    public function testInlinePatternIsNotKept(): void
    {
        $this->load('A : "\+" ;');

        $rule = $this->parser->initial;

        self::assertInstanceOf(TerminalRuleDefinition::class, $rule);
        self::assertFalse($rule->isKept);
    }

    #[TestDox('A token reference says whether the token is kept in the tree')]
    public function testTokenReferenceIsKept(): void
    {
        $this->load('A : <T_KEPT> ::T_SKIPPED:: ;');

        [$kept, $skipped] = $this->parser->initial?->children ?? [];

        self::assertInstanceOf(TerminalRuleDefinition::class, $kept);
        self::assertInstanceOf(TerminalRuleDefinition::class, $skipped);

        self::assertTrue($kept->isKept);
        self::assertFalse($skipped->isKept);
    }

    #[TestDox('The rule declared first is where the analysis starts')]
    public function testFirstRuleIsInitial(): void
    {
        $this->load('A : <T_A> ; B : <T_B> ;');

        self::assertSame('A', $this->parser->initial?->name);
    }

    #[TestDox('The root pragma names the rule the analysis starts at')]
    public function testRootPragmaMarksTheInitialRule(): void
    {
        $this->load('A : <T_A> ; %pragma root B B : <T_B> ;');

        $initial = $this->parser->initial;

        self::assertInstanceOf(RuleReference::class, $initial);
        self::assertSame('B', $initial->target);
    }

    #[TestDox('A pragma of an unknown name is reported')]
    public function testUnknownPragmaIsReported(): void
    {
        $this->expectException(UnsupportedPragmaException::class);
        $this->expectExceptionMessage('Unrecognized pragma "check_tokens"');

        $this->load('%pragma check_tokens false');
    }

    #[TestDox('A rule marked by "#" builds a node of its own')]
    public function testKeptRuleBuildsANodeOfItsOwn(): void
    {
        $this->load('#A : <T_A> ;');

        $reducer = $this->parser->initial?->reducer;

        self::assertInstanceOf(PhpCodeReducer::class, $reducer);
        self::assertSame('return $children;', $reducer->code);
    }

    #[TestDox('A reducer written as code is kept as it is written')]
    public function testCodeReducer(): void
    {
        $this->load('A -> { return 42; } : <T_A> ;');

        $reducer = $this->parser->initial?->reducer;

        self::assertInstanceOf(PhpCodeReducer::class, $reducer);
        self::assertSame('return 42;', $reducer->code);
    }

    #[TestDox('A reducer written as a class name builds an instance of it')]
    public function testClassReducer(): void
    {
        $this->load('A -> \App\Node : <T_A> ;');

        $reducer = $this->parser->initial?->reducer;

        self::assertInstanceOf(PhpCodeReducer::class, $reducer);
        self::assertSame('return new \App\Node($ctx, $children);', $reducer->code);
    }

    #[TestDox('A rule written of nothing but a reference is a rule of its own')]
    public function testRuleOfASingleReferenceIsNamed(): void
    {
        $this->load('A : B() ; B : <T_B> ;');

        $initial = $this->parser->initial;

        self::assertSame('A', $initial?->name);
        self::assertNotInstanceOf(RuleReference::class, $initial);
    }

    #[TestDox('A rule refers to the place of the grammar it is declared in')]
    public function testRuleRefersToItsDeclaration(): void
    {
        $source = 'A -> { return 42; } : "\+" ;';

        $this->load($source);

        self::assertSame('A -> { return 42; } : "\+"', $this->readSource($source, $this->parser->initial));
    }

    #[TestDox('A reference to another grammar is given away instead of being read')]
    public function testReferenceIsGivenAway(): void
    {
        $references = $this->load('%include grammar/lexemes');

        self::assertCount(1, $references);
        self::assertSame('grammar/lexemes', $references[0]->target);
        self::assertSame(0, $references[0]->offset);
        self::assertSame(24, $references[0]->length);
    }

    /**
     * @return list<GrammarReference>
     */
    private function load(string $source, string $pathname = self::PATHNAME): array
    {
        $result = new PP2Loader()
            ->load(new VirtualFile($pathname, $source), $this->parser, $this->lexer);

        return \iterator_to_array($result, false);
    }

    /**
     * Returns the fragment of the grammar the given definition has been read
     * from, so that the position it refers to is compared the way it is
     * written.
     */
    private function readSource(string $source, TokenDefinition|RuleDefinition|null $definition): string
    {
        $context = $definition?->context;

        self::assertNotNull($context);
        self::assertInstanceOf(FileInterface::class, $context->source);
        self::assertSame(self::PATHNAME, $context->source->pathname);

        return \substr($source, $context->offset, $context->length);
    }
}
