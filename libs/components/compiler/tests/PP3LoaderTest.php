<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\CompilerResult;
use Phplrt\Compiler\Exception\EmptyLexerException;
use Phplrt\Compiler\Exception\UnsupportedPragmaValueException;
use Phplrt\Compiler\Exception\UnsupportedTokenActionException;
use Phplrt\Compiler\Syntax\PP3\PP3Loader;
use Phplrt\Compiler\Tests\Stub\LexerPassStub;
use Phplrt\Compiler\Tests\Stub\ParserPassStub;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\UserDefinedChannel;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\PhpCodeEmbeddedLexer;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TransitionType;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Compiler\NestedConcatenationParserCompilerPass;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\Source;
use Phplrt\Source\VirtualFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Describes what the PP3 format says differently from the PP2 one.
 */
#[Group('phplrt/compiler')]
final class PP3LoaderTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string PATHNAME = '/app/grammar.pp3';

    private LexerBuilder $lexer;

    private ParserBuilder $parser;

    protected function setUp(): void
    {
        $this->lexer = new LexerBuilder();
        $this->parser = new ParserBuilder();
    }

    #[TestDox('A rule is separated from what it recognizes by a colon')]
    public function testColonSeparatesARule(): void
    {
        $this->load("%token T_A a\nA : <T_A> ;");

        self::assertNotNull($this->parser->initial);
    }

    #[TestDox('A rule separated by anything but a colon is reported')]
    public function testOtherSeparatorsAreReported(): void
    {
        foreach (['=', '::='] as $separator) {
            $this->parser = new ParserBuilder();
            $this->lexer = new LexerBuilder();

            try {
                $this->load(\sprintf("%%token T_A a\nA %s <T_A> ;", $separator));

                self::fail(\sprintf('The "%s" separator has been accepted', $separator));
            } catch (UnexpectedTokenException) {
                self::assertTrue(true);
            }
        }
    }

    #[TestDox('A rule marked by "#" is reported')]
    public function testKeptMarkerIsReported(): void
    {
        $this->expectException(UnexpectedTokenException::class);

        $this->load("%token T_A a\n#A : <T_A> ;");
    }

    #[TestDox('A reducer written as a class name is reported')]
    public function testClassReducerIsReported(): void
    {
        $this->expectException(UnexpectedTokenException::class);

        $this->load("%token T_A a\nA -> \\App\\Node : <T_A> ;");
    }

    #[TestDox('A statement written after "&" is recognized without being read')]
    public function testExpectedPredicate(): void
    {
        $parser = $this->compile(<<<'PP3'
            %token T_A a
            %token T_B b

            Pair -> { return \count($children); }
              : &<T_A> <T_A> <T_B>
              ;
            PP3);

        // The predicate reads nothing, so the "a" is still there to be read
        // and never reaches the rule twice
        self::assertSame(2, $parser->parse(new Source('ab')));
    }

    #[TestDox('A statement written after "!" is recognized when the statement is not')]
    public function testUnexpectedPredicate(): void
    {
        $parser = $this->compile(<<<'PP3'
            %skip  T_WHITESPACE \s++
            %token T_INT        \d++
            %token T_NAME       [a-z]++

            %pragma root Lonely

            Lonely -> { return $children[0]->value; }
              : <T_INT> !<T_NAME>
              ;
            PP3);

        self::assertSame('42', $parser->parse(new Source('42')));

        $this->expectException(UnexpectedTokenException::class);

        $parser->parse(new Source('42 beta'));
    }

    #[TestDox('A predicate stands before the quantifier of the statement it looks at')]
    public function testPredicateAppliesToTheQuantifiedStatement(): void
    {
        $parser = $this->compile(<<<'PP3'
            %token T_A a
            %token T_B b

            Pair -> { return \count($children); }
              : &<T_A>+ <T_A> <T_A> <T_B>
              ;
            PP3);

        self::assertSame(3, $parser->parse(new Source('aab')));
    }

    #[TestDox('A reducer written as code is read')]
    public function testCodeReducerIsRead(): void
    {
        $this->load("%token T_A a\nA -> { return 42; } : <T_A> ;");

        $reducer = $this->parser->initial?->reducer;

        self::assertInstanceOf(PhpCodeReducer::class, $reducer);
        self::assertSame('return 42;', $reducer->code);
    }

    #[TestDox('The "state" action hands the reading over to the lexer of that state')]
    public function testStateActionEntersALexer(): void
    {
        $this->load("%token T_QUOTE \" -> state(string)\n%token string:T_TEXT [^\"]++");

        [$quote] = \array_values($this->lexer->tokens);

        self::assertSame(TransitionType::Enter, $quote->transition?->type);
        self::assertSame('string', $quote->transition->lexer);
    }

    #[TestDox('The "exit" action gives the control back')]
    public function testExitActionLeavesALexer(): void
    {
        $this->load("%token T_QUOTE \" -> state(string)\n%token string:T_CLOSE \" -> exit()");

        $nested = $this->lexer->lexers['string'] ?? null;

        self::assertInstanceOf(LexerBuilder::class, $nested);

        [$close] = \array_values($nested->tokens);

        self::assertSame(TransitionType::Exit, $close->transition?->type);
    }

    #[TestDox('The "channel" action emits the token to that channel')]
    public function testChannelActionSetsTheChannel(): void
    {
        $this->load('%token T_COMMENT //[^\n]*+ -> channel(comments)');

        [$comment] = \array_values($this->lexer->tokens);

        self::assertEquals(new UserDefinedChannel('comments'), $comment->channel);
    }

    #[TestDox('The "channel" action names a built-in channel as well')]
    public function testChannelActionSetsABuiltInChannel(): void
    {
        $this->load('%token T_WHITESPACE \s++ -> channel(Hidden)');

        [$whitespace] = \array_values($this->lexer->tokens);

        self::assertSame(Channel::Hidden, $whitespace->channel);
        self::assertTrue($whitespace->isHidden);
    }

    #[TestDox('An action the compiler knows nothing about is reported')]
    public function testUnknownActionIsReported(): void
    {
        $this->expectException(UnsupportedTokenActionException::class);
        $this->expectExceptionMessage('Unrecognized token action "skip"');

        $this->load('%token T_A a -> skip()');
    }

    #[TestDox('An action written without the value it needs is reported')]
    public function testActionWithoutValueIsReported(): void
    {
        $this->expectException(UnsupportedTokenActionException::class);
        $this->expectExceptionMessage('The "state" action of a token expects a value');

        $this->load('%token T_A a -> state()');
    }

    #[TestDox('An action written with a value it does nothing with is reported')]
    public function testActionWithUnexpectedValueIsReported(): void
    {
        $this->expectException(UnsupportedTokenActionException::class);
        $this->expectExceptionMessage('The "exit" action of a token expects no value');

        $this->load('%token T_A a -> exit(somewhere)');
    }

    #[TestDox('An action is pointed at by the place it is written at')]
    public function testActionRefersToItsDeclaration(): void
    {
        $source = '%token T_A a -> skip()';

        try {
            $this->load($source);
        } catch (UnsupportedTokenActionException $e) {
            self::assertSame(\strpos($source, 'skip()'), $e->offset);
            self::assertSame(\strlen('skip()'), $e->length);

            return;
        }

        self::fail('The action has been accepted');
    }

    #[TestDox('A token does everything it is written to do')]
    public function testSeveralActionsAreApplied(): void
    {
        $this->load("%token T_QUOTE \" -> state(string), channel(strings)\n%token string:T_TEXT [^\"]++");

        [$quote] = \array_values($this->lexer->tokens);

        self::assertSame(TransitionType::Enter, $quote->transition?->type);
        self::assertSame('string', $quote->transition->lexer);
        self::assertEquals(new UserDefinedChannel('strings'), $quote->channel);
    }

    #[TestDox('The order the actions are written in does not matter')]
    public function testSeveralActionsAreAppliedInAnyOrder(): void
    {
        $this->load("%token T_QUOTE \" -> channel(strings), state(string)\n%token string:T_TEXT [^\"]++");

        [$quote] = \array_values($this->lexer->tokens);

        self::assertSame(TransitionType::Enter, $quote->transition?->type);
        self::assertEquals(new UserDefinedChannel('strings'), $quote->channel);
    }

    #[TestDox('A token moving the reading twice is reported')]
    public function testSeveralTransitionsAreReported(): void
    {
        $this->expectException(UnsupportedTokenActionException::class);
        $this->expectExceptionMessage(
            'A token is read once, so the "exit" action cannot be applied after the "state" one',
        );

        $this->load("%token T_QUOTE \" -> state(string), exit()\n%token string:T_TEXT [^\"]++");
    }

    #[TestDox('A token naming the state it switches to is no longer read')]
    public function testStateNameIsNotAnAction(): void
    {
        $this->expectException(UnexpectedTokenException::class);

        $this->load("%token T_QUOTE \" -> string\n%token string:T_TEXT [^\"]++");
    }

    #[TestDox('An expression beginning with an arrow is still read as an expression')]
    public function testPatternBeginningWithAnArrow(): void
    {
        $this->load('%token T_PHP ->\\s*+(?=\\{) -> state(php)');

        [$php] = \array_values($this->lexer->tokens);

        self::assertInstanceOf(RegexTokenDefinition::class, $php);
        self::assertSame('->\\s*+(?=\\{)', $php->regex);
        self::assertSame(TransitionType::Enter, $php->transition?->type);
    }

    #[TestDox('An expression spelled like a name is still read as an expression')]
    public function testPatternSpelledLikeAName(): void
    {
        $this->load("%token T_TRUE true -> channel(literals)\n%token T_FALSE false");

        [$true, $false] = \array_values($this->lexer->tokens);

        self::assertInstanceOf(RegexTokenDefinition::class, $true);
        self::assertSame('true', $true->regex);
        self::assertInstanceOf(RegexTokenDefinition::class, $false);
        self::assertSame('false', $false->regex);
    }

    #[TestDox('A part of a declaration written twice is reported')]
    public function testDeclarationWrittenTwiceIsReported(): void
    {
        $this->expectException(UnexpectedTokenException::class);

        $this->load('%token T_A a b');
    }

    #[TestDox('A statement written of a value declares the token reading exactly it')]
    public function testInlineValue(): void
    {
        $result = $this->build(<<<'PP3'
            %token T_NUMBER \d++

            Sum -> { return (int) $children[0]->value + (int) $children[1]->value; }
              : <T_NUMBER> "+" <T_NUMBER>
              ;
            PP3);

        $parser = $result->parser->toParser($result->lexer->toLexer());

        // The value is read as it is written, so the "+" of a regular
        // expression is only a plus sign here
        self::assertSame(3, $parser->parse(new Source('1+2')));
    }

    #[TestDox('A statement written of an expression declares the token recognizing it')]
    public function testInlinePattern(): void
    {
        $result = $this->build(<<<'PP3'
            %skip  T_WHITESPACE \s++
            %token T_NUMBER     \d++

            Expr -> { return \count($children); }
              : <T_NUMBER> /and|or|xor/ <T_NUMBER>
              ;
            PP3);

        $parser = $result->parser->toParser($result->lexer->toLexer());

        self::assertSame(2, $parser->parse(new Source('1 and 2')));
        self::assertSame(2, $parser->parse(new Source('1 xor 2')));
    }

    #[TestDox('The same value written in several rules declares a single token')]
    public function testInlineValueIsDeclaredOnce(): void
    {
        $this->load(<<<'PP3'
            %token T_NUMBER \d++

            A : <T_NUMBER> "+" <T_NUMBER> ;
            B : "+" <T_NUMBER> ;
            PP3);

        // The named token and the single one both rules have declared
        self::assertCount(2, $this->lexer->tokens);
    }

    #[TestDox('A token declared for every state is added to each of them')]
    public function testSharedTokenReachesEveryState(): void
    {
        $result = $this->build(<<<'PP3'
            %skip  *:T_WHITESPACE  \s++
            %token T_QUOTE  "  -> state(string)
            %token string:T_TEXT  [^"]++
            %token string:T_CLOSE "  -> exit()
            A : <T_QUOTE> ;
            PP3);

        self::assertContains('T_WHITESPACE', $result->lexer->names);

        $nested = $result->lexer->lexers['string'] ?? null;

        self::assertInstanceOf(LexerBuilderResult::class, $nested);
        self::assertContains('T_WHITESPACE', $nested->names);
    }

    #[TestDox('A token declared for every state reaches a state declared after it')]
    public function testSharedTokenReachesALaterState(): void
    {
        $result = $this->build(<<<'PP3'
            %skip  *:T_WHITESPACE  \s++
            %token T_A a -> state(one)
            %token one:T_B b -> exit()
            A : <T_A> ;
            PP3);

        $nested = $result->lexer->lexers['one'] ?? null;

        self::assertInstanceOf(LexerBuilderResult::class, $nested);
        self::assertContains('T_WHITESPACE', $nested->names);
    }

    #[TestDox('A lexer written by hand is not given the shared tokens')]
    public function testSharedTokenSkipsAnEmbeddedLexer(): void
    {
        $result = $this->build(<<<'PP3'
            %skip  *:T_WHITESPACE  \s++
            %token T_OPEN a -> state(php)
            %lexer php -> { new \App\PhpLexer() }
            A : <T_OPEN> ;
            PP3);

        self::assertInstanceOf(PhpCodeEmbeddedLexer::class, $result->lexer->lexers['php'] ?? null);
    }

    #[TestDox('A lexer is declared as the code building it')]
    public function testLexerDeclaration(): void
    {
        $this->load('%lexer php -> { new \App\PhpLexer() }');

        $lexer = $this->lexer->lexers['php'] ?? null;

        self::assertInstanceOf(PhpCodeEmbeddedLexer::class, $lexer);
        self::assertSame('new \App\PhpLexer()', $lexer->code);
    }

    #[TestDox('A lexer written of no code is reported')]
    public function testEmptyLexerDeclarationIsReported(): void
    {
        $this->expectException(EmptyLexerException::class);

        $this->load('%lexer php -> {}');
    }

    #[TestDox('A PCRE modifier is enabled by its name and by its value alike')]
    public function testPcreFlagPragma(): void
    {
        foreach (['Caseless', 'i'] as $flag) {
            $this->lexer = new LexerBuilder();

            $this->load(\sprintf('%%pragma lexer.pcre.flag %s', $flag));

            self::assertArrayHasKey('i', $this->lexer->flags);
        }
    }

    #[TestDox('A PCRE modifier is disabled by a setting of the grammar')]
    public function testPcreDisablePragma(): void
    {
        self::assertArrayHasKey('u', $this->lexer->flags);

        $this->load('%pragma lexer.pcre.disable Utf8');

        self::assertArrayNotHasKey('u', $this->lexer->flags);
    }

    #[TestDox('A modifier the compiler knows nothing about is reported')]
    public function testUnknownPcreFlagIsReported(): void
    {
        $this->expectException(UnsupportedPragmaValueException::class);

        $this->load('%pragma lexer.pcre.flag Nope');
    }

    #[TestDox('A pass is registered at the priority its setting is named after')]
    public function testPassPragmas(): void
    {
        $this->load(\sprintf(
            "%%pragma lexer.check \\%s\n%%pragma parser.optimize \\%s",
            LexerPassStub::class,
            ParserPassStub::class,
        ));

        self::assertContainsOnlyInstancesOf(
            LexerPassStub::class,
            \array_filter(
                $this->lexer->compilerPasses[LexerBuilder::PASS_PRIORITY_CHECK],
                static fn(object $pass): bool => $pass instanceof LexerPassStub,
            ),
        );

        $optimize = $this->parser->compilerPasses[ParserBuilder::PASS_PRIORITY_OPTIMIZE];

        self::assertNotEmpty(\array_filter(
            $optimize,
            static fn(object $pass): bool => $pass instanceof ParserPassStub,
        ));
    }

    #[TestDox('A pass is dropped by a setting of the grammar')]
    public function testDisablePassPragma(): void
    {
        $this->load(\sprintf('%%pragma parser.disable \\%s', NestedConcatenationParserCompilerPass::class));

        foreach ($this->parser->compilerPasses as $passes) {
            foreach ($passes as $pass) {
                self::assertNotInstanceOf(NestedConcatenationParserCompilerPass::class, $pass);
            }
        }
    }

    #[TestDox('A pass that does not exist is reported')]
    public function testUnknownPassIsReported(): void
    {
        $this->expectException(UnsupportedPragmaValueException::class);

        $this->load('%pragma lexer.check \No\Such\Pass');
    }

    #[TestDox('A pass of the wrong kind is reported')]
    public function testPassOfTheWrongKindIsReported(): void
    {
        $this->expectException(UnsupportedPragmaValueException::class);

        $this->load(\sprintf('%%pragma lexer.check \\%s', ParserPassStub::class));
    }

    private function build(string $source): CompilerResult
    {
        $compiler = new Compiler();
        $compiler->load(new VirtualFile(self::PATHNAME, $source));

        return $compiler->build();
    }

    private function compile(string $source): ParserInterface
    {
        $compiler = new Compiler();
        $compiler->load(new VirtualFile(self::PATHNAME, $source));

        return $compiler->getParser();
    }

    /**
     * @return list<mixed>
     */
    private function load(string $source, string $pathname = self::PATHNAME): array
    {
        $result = new PP3Loader()
            ->load(new VirtualFile($pathname, $source), $this->parser, $this->lexer);

        return \iterator_to_array($result, false);
    }
}
