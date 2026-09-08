<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Exception\UnsupportedPragmaException;
use Phplrt\Compiler\Exception\UnsupportedTransitionException;
use Phplrt\Compiler\Syntax\PP2\PP2Loader;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\Definition\TransitionType;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleReference;
use Phplrt\Parser\Builder\Definition\TerminalRuleDefinition;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class PP2LoaderTest extends TestCase
{
    private const PATHNAME = '/app/grammar.pp2';

    private LexerBuilder $lexer;

    private ParserBuilder $parser;

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->lexer = new LexerBuilder();
        $this->parser = new ParserBuilder();
    }

    public function testTokenIsAddedToTheLexer(): void
    {
        $this->load(<<<'PP2'
            %token T_NUMBER \d++
            %skip  T_WHITESPACE \s++
            PP2);

        [$number, $whitespace] = \array_values($this->lexer->tokens);

        Assert::same($number->name, 'T_NUMBER');
        Assert::false($number->isHidden);

        Assert::same($whitespace->name, 'T_WHITESPACE');
        Assert::true($whitespace->isHidden);
    }

    public function testTokenRefersToItsDeclaration(): void
    {
        $source = <<<'PP2'
            %skip T_WHITESPACE \s++
            %token T_NUMBER \d++
            PP2;

        $this->load($source);

        Assert::same($this->readSource($source, $this->lexer->tokens[1]), '%token T_NUMBER \d++');
    }

    public function testStateIsReadByALexerOfItsOwn(): void
    {
        $this->load(<<<'PP2'
            %token        T_QUOTE "      -> string
            %token string:T_TEXT  [^"]++
            %token string:T_END   "      -> default
            PP2);

        Assert::same(\array_keys($this->lexer->lexers), ['string']);

        $nested = $this->lexer->lexers['string'];

        Assert::instanceOf($nested, LexerBuilder::class);
        Assert::same($this->lexer->tokens[0]->transition?->type, TransitionType::Enter);
        Assert::same($this->lexer->tokens[0]->transition?->lexer, 'string');
        Assert::null($nested->tokens[0]->transition);
        Assert::same($nested->tokens[1]->transition?->type, TransitionType::Exit);
    }

    public function testTransitionBetweenNamedStatesIsReported(): void
    {
        Expect::exception(UnsupportedTransitionException::class)
        ->withMessageContaining('cannot be continued by the state "second"');

        $this->load('%token first:T_X x -> second');
    }

    public function testInlinePatternIsReadByAnAnonymousToken(): void
    {
        $this->load('A : "\+" B() ; B : "\+" ;');

        Assert::count($this->lexer->tokens, 1);

        $token = $this->lexer->tokens[0];

        Assert::instanceOf($token, RegexTokenDefinition::class);
        Assert::null($token->name);
        Assert::same($token->regex, '\+');
    }

    public function testInlinePatternIsNotKept(): void
    {
        $this->load('A : "\+" ;');

        [$terminal] = $this->parser->initial?->children ?? [];

        Assert::instanceOf($terminal, TerminalRuleDefinition::class);
        Assert::false($terminal->isKept);
    }

    public function testKeptRuleOfASingleTokenIsAProduction(): void
    {
        $this->load('#A ::= <T_A> ;');

        $rule = $this->parser->initial;

        Assert::instanceOf($rule, ConcatenationRuleDefinition::class);
        Assert::true($rule->isKept);

        [$terminal] = $rule->children;

        Assert::instanceOf($terminal, TerminalRuleDefinition::class);
        Assert::true($terminal->isKept);
    }

    public function testEntrypointsContainTheKeptRulesOnly(): void
    {
        $result = (new Compiler())
            ->load(VirtualSource::createFromString(self::PATHNAME, <<<'PP2'
                %token T_A a
                %token T_B b
                %token T_C c

                A ::= <T_A> Plain() ;

                Plain ::= <T_B> ;

                #Kept ::= <T_C> ;
                PP2))
            ->build();

        $entrypoints = $result->parser->entrypoints;

        Assert::same(\array_keys($entrypoints), ['Kept']);
        Assert::same($entrypoints['Kept'], $result->parser->constants['Kept'] ?? null);
        Assert::notNull($result->parser->constants['A'] ?? null);
        Assert::notNull($result->parser->constants['Plain'] ?? null);
    }

    public function testRuleOfASkippedTokenIsAProduction(): void
    {
        $this->load('A : ::T_A:: ;');

        $rule = $this->parser->initial;

        Assert::instanceOf($rule, ConcatenationRuleDefinition::class);
        Assert::same($rule->name, 'A');

        [$terminal] = $rule->children;

        Assert::instanceOf($terminal, TerminalRuleDefinition::class);
        Assert::false($terminal->isKept);
    }

    public function testTokenReferenceIsKept(): void
    {
        $this->load('A : <T_KEPT> ::T_SKIPPED:: ;');

        [$kept, $skipped] = $this->parser->initial?->children ?? [];

        Assert::instanceOf($kept, TerminalRuleDefinition::class);
        Assert::instanceOf($skipped, TerminalRuleDefinition::class);

        Assert::true($kept->isKept);
        Assert::false($skipped->isKept);
    }

    public function testFirstRuleIsInitial(): void
    {
        $this->load('A : <T_A> ; B : <T_B> ;');

        Assert::same($this->parser->initial?->name, 'A');
    }

    public function testRootPragmaMarksTheInitialRule(): void
    {
        $this->load('A : <T_A> ; %pragma root B B : <T_B> ;');

        $initial = $this->parser->initial;

        Assert::instanceOf($initial, RuleReference::class);
        Assert::same($initial->target, 'B');
    }

    public function testUnknownPragmaIsReported(): void
    {
        Expect::exception(UnsupportedPragmaException::class)
        ->withMessage('Unrecognized pragma "check_tokens"');

        $this->load('%pragma check_tokens false');
    }

    public function testKeptRuleBuildsNoNodeOfItsOwn(): void
    {
        $this->load('#A : <T_A> ;');

        Assert::null($this->parser->initial?->reducer);
    }

    public function testCodeReducer(): void
    {
        $this->load('A -> { return 42; } : <T_A> ;');

        $reducer = $this->parser->initial?->reducer;

        Assert::instanceOf($reducer, PhpCodeReducer::class);
        Assert::same($reducer->code, 'return 42;');
    }

    public function testCodeReducerIsDedented(): void
    {
        $this->load(<<<'PP2'
            A -> {
                if ($children === null) {
                    return null;
                }

                return 42;
            }
              : <T_A>
              ;
            PP2);

        $reducer = $this->parser->initial?->reducer;

        Assert::instanceOf($reducer, PhpCodeReducer::class);
        Assert::same($reducer->code, <<<'PHP'
            if ($children === null) {
                return null;
            }

            return 42;
            PHP);
    }

    public function testReducerVariablesAreDeclared(): void
    {
        $this->load('A -> { return $end === $offset + $length; } : <T_A> ;');

        $reducer = $this->parser->initial?->reducer;

        Assert::instanceOf($reducer, PhpCodeReducer::class);
        Assert::string($reducer->code)->contains("\$offset = \$ctx->begin;\n");
        Assert::string($reducer->code)->contains("\$length = \$ctx->length;\n");
        Assert::string($reducer->code)->contains("\$end = \$ctx->begin + \$ctx->length;\n");
        Assert::true(\str_ends_with($reducer->code, 'return $end === $offset + $length;'));
    }

    public function testUnusedReducerVariablesAreNotDeclared(): void
    {
        $this->load('A -> { return $children; } : <T_A> ;');

        $reducer = $this->parser->initial?->reducer;

        Assert::instanceOf($reducer, PhpCodeReducer::class);
        Assert::same($reducer->code, 'return $children;');
    }

    public function testReducerVariablesAreReadTheWayPhpReadsThem(): void
    {
        $this->load('A -> { return \'$offset\'; } : <T_A> ;');

        $reducer = $this->parser->initial?->reducer;

        Assert::instanceOf($reducer, PhpCodeReducer::class);
        Assert::same($reducer->code, 'return \'$offset\';');
    }

    public function testClassReducer(): void
    {
        $this->load('A -> \App\Node : <T_A> ;');

        $reducer = $this->parser->initial?->reducer;

        Assert::instanceOf($reducer, PhpCodeReducer::class);
        Assert::same($reducer->code, 'return new \App\Node($ctx, $children);');
    }

    public function testRuleOfASingleReferenceIsNamed(): void
    {
        $this->load('A : B() ; B : <T_B> ;');

        $initial = $this->parser->initial;

        Assert::same($initial?->name, 'A');
        Assert::false($initial instanceof RuleReference);
    }

    public function testRuleRefersToItsDeclaration(): void
    {
        $source = 'A -> { return 42; } : "\+" ;';

        $this->load($source);

        Assert::same($this->readSource($source, $this->parser->initial), 'A -> { return 42; } : "\+"');
    }

    #[DataSet(['&'], 'and predicate')]
    #[DataSet(['!'], 'not predicate')]
    public function testPredicatesAreReported(string $sign): never
    {
        Expect::exception(UnexpectedTokenException::class);

        $this->load(\sprintf("%%token T_A a\nA : %s<T_A> ;", $sign));
    }

    public function testReferenceIsGivenAway(): void
    {
        $references = $this->load('%include grammar/lexemes');

        Assert::count($references, 1);
        Assert::same($references[0]->target, 'grammar/lexemes');
        Assert::same($references[0]->offset, 0);
        Assert::same($references[0]->length, 24);
    }

    private function load(string $source, string $pathname = self::PATHNAME): array
    {
        $result = (new PP2Loader())
            ->load(VirtualSource::createFromString($pathname, $source), $this->parser, $this->lexer);

        return \iterator_to_array($result, false);
    }

    private function readSource(string $source, TokenDefinition|RuleDefinition|null $definition): string
    {
        $context = $definition?->context;

        Assert::notNull($context);
        Assert::instanceOf($context->source, FileInterface::class);
        Assert::same($context->source->pathname, self::PATHNAME);

        return \substr($source, $context->offset, $context->length);
    }
}
