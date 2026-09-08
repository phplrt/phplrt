<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\CompilerResult;
use Phplrt\Compiler\Exception\EmptyLexerException;
use Phplrt\Compiler\Exception\UnsupportedPragmaValueException;
use Phplrt\Compiler\Exception\UnsupportedTokenActionException;
use Phplrt\Compiler\Node\Declaration\RuleDeclaration;
use Phplrt\Compiler\Node\Reducer\CodeReducer;
use Phplrt\Compiler\Syntax\PP3\PP3Loader;
use Phplrt\Compiler\Syntax\PP3\PP3Parser;
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
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Definition\TerminalRuleDefinition;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class PP3LoaderTest extends TestCase
{
    private const PATHNAME = '/app/grammar.pp3';

    private LexerBuilder $lexer;

    private ParserBuilder $parser;

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->lexer = new LexerBuilder();
        $this->parser = new ParserBuilder();
    }

    public function testColonSeparatesARule(): void
    {
        $this->load("%token T_A a\nA : <T_A> ;");

        Assert::notNull($this->parser->initial);
    }

    #[DataSet(['='], 'equals sign')]
    #[DataSet(['::='], 'bnf arrow')]
    public function testOtherSeparatorsAreReported(string $separator): never
    {
        Expect::exception(UnexpectedTokenException::class);

        $this->load(\sprintf("%%token T_A a\nA %s <T_A> ;", $separator));
    }

    public function testKeptMarkerIsRead(): void
    {
        $declaration = self::readRule('#A : <T_A> ;');

        Assert::same($declaration->name, 'A');
        Assert::true($declaration->isKept);
    }

    public function testRuleIsNotKeptWithoutTheMarker(): void
    {
        $declaration = self::readRule('A : <T_A> ;');

        Assert::same($declaration->name, 'A');
        Assert::false($declaration->isKept);
    }

    public function testKeptMarkerIsReadAlongWithAReducer(): void
    {
        $declaration = self::readRule('#A -> { return 42; } : <T_A> ;');

        Assert::true($declaration->isKept);
        Assert::instanceOf($declaration->reducer, CodeReducer::class);
    }

    public function testKeptMarkerWithoutANameIsReported(): void
    {
        Expect::exception(UnexpectedTokenException::class);

        self::readRule('# : <T_A> ;');
    }

    public function testKeptRuleIsNotRemovedWhenUnreachable(): void
    {
        $result = (new Compiler())
            ->load(VirtualSource::createFromString(self::PATHNAME, <<<'PP3'
                %token T_A a
                %token T_B b

                A : <T_A> ;

                #B : <T_B> ;
                PP3))
            ->build();

        Assert::same($result->parser->initial, $result->parser->constants['A'] ?? null);
        Assert::notNull($result->parser->constants['B'] ?? null);
    }

    public function testKeptRuleOfASingleTokenIsAProduction(): void
    {
        $this->load("%token T_A a\n#A : <T_A> ;");

        $rule = $this->parser->initial;

        Assert::instanceOf($rule, ConcatenationRuleDefinition::class);
        Assert::true($rule->isKept);

        [$terminal] = $rule->children;

        Assert::instanceOf($terminal, TerminalRuleDefinition::class);
        Assert::true($terminal->isKept);
    }

    public function testClassReducerIsReported(): void
    {
        Expect::exception(UnexpectedTokenException::class);

        $this->load("%token T_A a\nA -> \\App\\Node : <T_A> ;");
    }

    public function testReducerOfASkippedTokenIsCalled(): void
    {
        $parser = $this->compile(<<<'PP3'
            %token T_A a

            A -> { return 42; }
              : ::T_A::
              ;
            PP3);

        Assert::same($parser->parse(StringSource::createFromString('a')), 42);
    }

    public function testReducerOfANestedSkippedTokenIsCalled(): void
    {
        $parser = $this->compile(<<<'PP3'
            %token T_A a

            A -> { return $children; }
              : B()
              ;

            B -> { return 42; }
              : ::T_A::
              ;
            PP3);

        Assert::same($parser->parse(StringSource::createFromString('a')), [42]);
    }

    public function testExpectedPredicate(): void
    {
        $parser = $this->compile(<<<'PP3'
            %token T_A a
            %token T_B b

            Pair -> { return \count($children); }
              : &<T_A> <T_A> <T_B>
              ;
            PP3);

        Assert::same($parser->parse(StringSource::createFromString('ab')), 2);
    }

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

        Assert::same($parser->parse(StringSource::createFromString('42')), '42');

        Expect::exception(UnexpectedTokenException::class);

        $parser->parse(StringSource::createFromString('42 beta'));
    }

    public function testPredicateAppliesToTheQuantifiedStatement(): void
    {
        $parser = $this->compile(<<<'PP3'
            %token T_A a
            %token T_B b

            Pair -> { return \count($children); }
              : &<T_A>+ <T_A> <T_A> <T_B>
              ;
            PP3);

        Assert::same($parser->parse(StringSource::createFromString('aab')), 3);
    }

    public function testCodeReducerIsRead(): void
    {
        $this->load("%token T_A a\nA -> { return 42; } : <T_A> ;");

        $reducer = $this->parser->initial?->reducer;

        Assert::instanceOf($reducer, PhpCodeReducer::class);
        Assert::same($reducer->code, 'return 42;');
    }

    public function testStateActionEntersALexer(): void
    {
        $this->load("%token T_QUOTE \" -> state(string)\n%token string:T_TEXT [^\"]++");

        [$quote] = \array_values($this->lexer->tokens);

        Assert::same($quote->transition?->type, TransitionType::Enter);
        Assert::same($quote->transition->lexer, 'string');
    }

    public function testExitActionLeavesALexer(): void
    {
        $this->load("%token T_QUOTE \" -> state(string)\n%token string:T_CLOSE \" -> exit()");

        $nested = $this->lexer->lexers['string'] ?? null;

        Assert::instanceOf($nested, LexerBuilder::class);

        [$close] = \array_values($nested->tokens);

        Assert::same($close->transition?->type, TransitionType::Exit);
    }

    public function testChannelActionSetsTheChannel(): void
    {
        $this->load('%token T_COMMENT //[^\n]*+ -> channel(comments)');

        [$comment] = \array_values($this->lexer->tokens);

        Assert::equals($comment->channel, new UserDefinedChannel('comments'));
    }

    public function testChannelActionSetsABuiltInChannel(): void
    {
        $this->load('%token T_WHITESPACE \s++ -> channel(Hidden)');

        [$whitespace] = \array_values($this->lexer->tokens);

        Assert::same($whitespace->channel, Channel::Hidden);
        Assert::true($whitespace->isHidden);
    }

    public function testUnknownActionIsReported(): void
    {
        Expect::exception(UnsupportedTokenActionException::class)
        ->withMessage('Unrecognized token action "skip"');

        $this->load('%token T_A a -> skip()');
    }

    public function testActionWithoutValueIsReported(): void
    {
        Expect::exception(UnsupportedTokenActionException::class)
        ->withMessage('The "state" action of a token expects a value');

        $this->load('%token T_A a -> state()');
    }

    public function testActionWithUnexpectedValueIsReported(): void
    {
        Expect::exception(UnsupportedTokenActionException::class)
        ->withMessage('The "exit" action of a token expects no value');

        $this->load('%token T_A a -> exit(somewhere)');
    }

    public function testActionRefersToItsDeclaration(): void
    {
        $source = '%token T_A a -> skip()';

        try {
            $this->load($source);
        } catch (UnsupportedTokenActionException $e) {
            Assert::same($e->offset, \strpos($source, 'skip()'));
            Assert::same($e->length, \strlen('skip()'));

            return;
        }

        Assert::fail('The action has been accepted');
    }

    public function testSeveralActionsAreApplied(): void
    {
        $this->load("%token T_QUOTE \" -> state(string), channel(strings)\n%token string:T_TEXT [^\"]++");

        [$quote] = \array_values($this->lexer->tokens);

        Assert::same($quote->transition?->type, TransitionType::Enter);
        Assert::same($quote->transition->lexer, 'string');
        Assert::equals($quote->channel, new UserDefinedChannel('strings'));
    }

    public function testSeveralActionsAreAppliedInAnyOrder(): void
    {
        $this->load("%token T_QUOTE \" -> channel(strings), state(string)\n%token string:T_TEXT [^\"]++");

        [$quote] = \array_values($this->lexer->tokens);

        Assert::same($quote->transition?->type, TransitionType::Enter);
        Assert::equals($quote->channel, new UserDefinedChannel('strings'));
    }

    public function testSeveralTransitionsAreReported(): void
    {
        Expect::exception(UnsupportedTokenActionException::class)
        ->withMessage('A token is read once, so the "exit" '
            . 'action cannot be applied after the "state" one');

        $this->load("%token T_QUOTE \" -> state(string), exit()\n%token string:T_TEXT [^\"]++");
    }

    public function testStateNameIsNotAnAction(): void
    {
        Expect::exception(UnexpectedTokenException::class);

        $this->load("%token T_QUOTE \" -> string\n%token string:T_TEXT [^\"]++");
    }

    public function testPatternBeginningWithAnArrow(): void
    {
        $this->load('%token T_PHP ->\\s*+(?=\\{) -> state(php)');

        [$php] = \array_values($this->lexer->tokens);

        Assert::instanceOf($php, RegexTokenDefinition::class);
        Assert::same($php->regex, '->\\s*+(?=\\{)');
        Assert::same($php->transition?->type, TransitionType::Enter);
    }

    public function testPatternSpelledLikeAName(): void
    {
        $this->load("%token T_TRUE true -> channel(literals)\n%token T_FALSE false");

        [$true, $false] = \array_values($this->lexer->tokens);

        Assert::instanceOf($true, RegexTokenDefinition::class);
        Assert::same($true->regex, 'true');
        Assert::instanceOf($false, RegexTokenDefinition::class);
        Assert::same($false->regex, 'false');
    }

    public function testDeclarationWrittenTwiceIsReported(): void
    {
        Expect::exception(UnexpectedTokenException::class);

        $this->load('%token T_A a b');
    }

    public function testInlineValue(): void
    {
        $result = $this->build(<<<'PP3'
            %token T_NUMBER \d++

            Sum -> { return (int) $children[0]->value + (int) $children[1]->value; }
              : <T_NUMBER> "+" <T_NUMBER>
              ;
            PP3);

        $parser = $result->parser->toParser($result->lexer->toLexer());

        Assert::same($parser->parse(StringSource::createFromString('1+2')), 3);
    }

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

        Assert::same($parser->parse(StringSource::createFromString('1 and 2')), 2);
        Assert::same($parser->parse(StringSource::createFromString('1 xor 2')), 2);
    }

    public function testInlineValueIsDeclaredOnce(): void
    {
        $this->load(<<<'PP3'
            %token T_NUMBER \d++

            A : <T_NUMBER> "+" <T_NUMBER> ;
            B : "+" <T_NUMBER> ;
            PP3);

        Assert::count($this->lexer->tokens, 2);
    }

    public function testSharedTokenReachesEveryState(): void
    {
        $result = $this->build(<<<'PP3'
            %skip  *:T_WHITESPACE  \s++
            %token T_QUOTE  "  -> state(string)
            %token string:T_TEXT  [^"]++
            %token string:T_CLOSE "  -> exit()
            A : <T_QUOTE> ;
            PP3);

        Assert::contains($result->lexer->names, 'T_WHITESPACE');

        $nested = $result->lexer->lexers['string'] ?? null;

        Assert::instanceOf($nested, LexerBuilderResult::class);
        Assert::contains($nested->names, 'T_WHITESPACE');
    }

    public function testSharedTokenKeepsItsOrder(): void
    {
        $result = $this->build(<<<'PP3'
            %token T_FIRST   a
            %skip  *:T_SHARED  \s++
            %token T_LAST    b

            A : <T_FIRST> ;
            PP3);

        Assert::same(\array_values($result->lexer->names), ['T_FIRST', 'T_SHARED', 'T_LAST']);
    }

    public function testSharedTokenReachesALaterState(): void
    {
        $result = $this->build(<<<'PP3'
            %skip  *:T_WHITESPACE  \s++
            %token T_A a -> state(one)
            %token one:T_B b -> exit()
            A : <T_A> ;
            PP3);

        $nested = $result->lexer->lexers['one'] ?? null;

        Assert::instanceOf($nested, LexerBuilderResult::class);
        Assert::contains($nested->names, 'T_WHITESPACE');
    }

    public function testSharedTokenSkipsAnEmbeddedLexer(): void
    {
        $result = $this->build(<<<'PP3'
            %skip  *:T_WHITESPACE  \s++
            %token T_OPEN a -> state(php)
            %lexer php -> { new \App\PhpLexer() }
            A : <T_OPEN> ;
            PP3);

        Assert::instanceOf($result->lexer->lexers['php'] ?? null, PhpCodeEmbeddedLexer::class);
    }

    public function testLexerDeclaration(): void
    {
        $this->load('%lexer php -> { new \App\PhpLexer() }');

        $lexer = $this->lexer->lexers['php'] ?? null;

        Assert::instanceOf($lexer, PhpCodeEmbeddedLexer::class);
        Assert::same($lexer->code, 'new \App\PhpLexer()');
    }

    public function testEmptyLexerDeclarationIsReported(): void
    {
        Expect::exception(EmptyLexerException::class);

        $this->load('%lexer php -> {}');
    }

    #[DataSet(['Caseless'], 'long name')]
    #[DataSet(['i'], 'short name')]
    public function testPcreFlagPragma(string $flag): void
    {
        $this->load(\sprintf('%%pragma lexer.pcre.flag %s', $flag));

        Assert::array($this->lexer->flags)->hasKeys('i');
    }

    public function testPcreDisablePragma(): void
    {
        Assert::array($this->lexer->flags)->hasKeys('u');

        $this->load('%pragma lexer.pcre.disable Utf8');

        Assert::array($this->lexer->flags)->doesNotHaveKeys('u');
    }

    public function testUnknownPcreFlagIsReported(): void
    {
        Expect::exception(UnsupportedPragmaValueException::class);

        $this->load('%pragma lexer.pcre.flag Nope');
    }

    public function testPassPragmas(): void
    {
        $this->load(\sprintf(
            "%%pragma lexer.check \\%s\n%%pragma parser.optimize \\%s",
            LexerPassStub::class,
            ParserPassStub::class,
        ));

        $checks = \array_filter(
            $this->lexer->compilerPasses[LexerBuilder::PASS_PRIORITY_CHECK],
            static fn(object $pass): bool => $pass instanceof LexerPassStub,
        );

        Assert::same([], \array_filter(
            $checks,
            static fn(object $pass): bool => !$pass instanceof LexerPassStub,
        ));

        $optimize = $this->parser->compilerPasses[ParserBuilder::PASS_PRIORITY_OPTIMIZE];

        Assert::notBlank(\array_filter(
            $optimize,
            static fn(object $pass): bool => $pass instanceof ParserPassStub,
        ));
    }

    public function testDisablePassPragma(): void
    {
        $this->load(\sprintf('%%pragma parser.disable \\%s', NestedConcatenationParserCompilerPass::class));

        foreach ($this->parser->compilerPasses as $passes) {
            foreach ($passes as $pass) {
                Assert::false($pass instanceof NestedConcatenationParserCompilerPass);
            }
        }
    }

    public function testUnknownPassIsReported(): void
    {
        Expect::exception(UnsupportedPragmaValueException::class);

        $this->load('%pragma lexer.check \No\Such\Pass');
    }

    public function testPassOfTheWrongKindIsReported(): void
    {
        Expect::exception(UnsupportedPragmaValueException::class);

        $this->load(\sprintf('%%pragma lexer.check \\%s', ParserPassStub::class));
    }

    private function build(string $source): CompilerResult
    {
        $compiler = new Compiler();
        $compiler->load(VirtualSource::createFromString(self::PATHNAME, $source));

        return $compiler->build();
    }

    private static function readRule(string $source): RuleDeclaration
    {
        $declarations = (new PP3Parser())
            ->parse(StringSource::createFromString($source));

        \assert(\is_array($declarations), 'A grammar file is read into a list of declarations');

        $declaration = $declarations[0] ?? null;

        Assert::instanceOf($declaration, RuleDeclaration::class);

        return $declaration;
    }

    private function compile(string $source): ParserInterface
    {
        $compiler = new Compiler();
        $compiler->load(VirtualSource::createFromString(self::PATHNAME, $source));

        return $compiler->getParser();
    }

    private function load(string $source, string $pathname = self::PATHNAME): array
    {
        $result = (new PP3Loader())
            ->load(VirtualSource::createFromString($pathname, $source), $this->parser, $this->lexer);

        return \iterator_to_array($result, false);
    }
}
