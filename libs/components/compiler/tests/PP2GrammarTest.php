<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Node\Declaration\RuleDeclaration;
use Phplrt\Compiler\Node\Declaration\TokenDeclaration;
use Phplrt\Compiler\Node\Reducer\CodeReducer;
use Phplrt\Compiler\Node\Statement\Concatenation;
use Phplrt\Compiler\Node\Statement\InlinePattern;
use Phplrt\Compiler\Syntax\PP2\PP2Parser;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class PP2GrammarTest extends TestCase
{
    public function testEmptyGrammar(): void
    {
        Assert::same(self::describe(''), []);
    }

    public function testCommentsOnlyGrammar(): void
    {
        Assert::same(self::describe(<<<'PP2'
            // A single line comment
            /*
             * And a multiline one
             */
            PP2), []);
    }

    public function testTokenDeclaration(): void
    {
        Assert::same(self::describe('%token T_NUMBER \d++'), [
            '%token T_NUMBER \d++',
        ]);
    }

    public function testSkippedTokenDeclaration(): void
    {
        Assert::same(self::describe('%skip T_WHITESPACE \s++'), [
            '%skip T_WHITESPACE \s++',
        ]);
    }

    public function testTokenDeclarationStates(): void
    {
        Assert::same(self::describe(<<<'PP2'
            %token        T_QUOTE  "        -> string
            %token string:T_CHAR   [^"]++
            %token string:T_END    "        -> default
            PP2), [
            '%token T_QUOTE " -> string',
            '%token string:T_CHAR [^"]++',
            '%token string:T_END " -> default',
        ]);
    }

    public function testTokenPatternSpelledLikeName(): void
    {
        Assert::same(self::describe('%token T_IF if'), [
            '%token T_IF if',
        ]);
    }

    public function testTokenPatternBeginningWithComment(): void
    {
        Assert::same(self::describe('%skip T_COMMENT //[^\n]*'), [
            '%skip T_COMMENT //[^\n]*',
        ]);
    }

    public function testCommentAfterDeclaration(): void
    {
        Assert::same(self::describe(<<<'PP2'
            %token   T_NUMBER  \d++              // A number
            %pragma  root      Sum               // Where the analysis starts
            %include grammar/lexemes             // The tokens
            PP2), [
            '%token T_NUMBER \d++',
            '%pragma root Sum',
            '%include grammar/lexemes',
        ]);
    }

    public function testPragmaDeclaration(): void
    {
        Assert::same(self::describe('%pragma parser.root Sum'), [
            '%pragma parser.root Sum',
        ]);
    }

    public function testIncludeDeclaration(): void
    {
        Assert::same(self::describe('%include grammar/lexemes'), [
            '%include grammar/lexemes',
        ]);
    }

    public function testRuleSeparators(): void
    {
        Assert::same(self::describe(<<<'PP2'
            A  :   <T_NUMBER> ;
            B  =   <T_NUMBER> ;
            C  ::= <T_NUMBER> ;
            PP2), [
            'A : <T_NUMBER> ;',
            'B : <T_NUMBER> ;',
            'C : <T_NUMBER> ;',
        ]);
    }

    public function testRuleWithoutSemicolon(): void
    {
        Assert::same(self::describe(<<<'PP2'
            A : <T_NUMBER>
            B : <T_NAME>
            PP2), [
            'A : <T_NUMBER> ;',
            'B : <T_NAME> ;',
        ]);
    }

    public function testKeptRule(): void
    {
        Assert::same(self::describe('#A : <T_NUMBER>;'), [
            '#A : <T_NUMBER> ;',
        ]);
    }

    public function testTokenReferences(): void
    {
        Assert::same(self::describe('A : <T_NUMBER> ::T_COMMA:: ;'), [
            'A : (<T_NUMBER> ::T_COMMA::) ;',
        ]);
    }

    public function testInlinePattern(): void
    {
        Assert::same(self::describe('A : "\+" ;'), [
            'A : "\+" ;',
        ]);
    }

    public function testInlinePatternQuotes(): void
    {
        $declarations = new PP2Parser()
            ->parse(StringSource::createFromString('A : "\"" ;'));

        $rule = $declarations[0];

        Assert::instanceOf($rule, RuleDeclaration::class);
        Assert::instanceOf($rule->body, InlinePattern::class);
        Assert::same($rule->body->pattern, '"');
    }

    public function testAlternation(): void
    {
        Assert::same(self::describe('A : <T_A> | <T_B> | <T_C> ;'), [
            'A : (<T_A> | <T_B> | <T_C>) ;',
        ]);
    }

    public function testGroup(): void
    {
        Assert::same(self::describe('A : <T_A> ( <T_B> | <T_C> ) ;'), [
            'A : (<T_A> (<T_B> | <T_C>)) ;',
        ]);
    }

    public function testSingleStatement(): void
    {
        Assert::same(self::describe('A : ( ( <T_A> ) ) ;'), [
            'A : <T_A> ;',
        ]);
    }

    public function testQuantifiers(): void
    {
        Assert::same(self::describe('A : <T_A>? <T_A>+ <T_A>* <T_A>{2,5} <T_A>{2,} <T_A>{,5} <T_A>{7} ;'), [
            'A : (<T_A>{0,1} <T_A>{1,} <T_A>{0,} <T_A>{2,5} <T_A>{2,} <T_A>{0,5} <T_A>{7,7}) ;',
        ]);
    }

    public function testQuantifiedGroup(): void
    {
        Assert::same(self::describe('A : ( <T_A> ::T_COMMA:: <T_A> )* ;'), [
            'A : (<T_A> ::T_COMMA:: <T_A>){0,} ;',
        ]);
    }

    public function testClassReducer(): void
    {
        Assert::same(self::describe('A -> \App\Node\SumNode : <T_A> ;'), [
            'A -> \App\Node\SumNode : <T_A> ;',
        ]);
    }

    public function testCodeReducer(): void
    {
        $declarations = new PP2Parser()->parse(StringSource::createFromString(<<<'PP2'
            A -> {
                if ($children === []) {
                    return null;
                }

                // A brace "}" written inside a comment
                return \implode('}', $children);
            } : <T_A> ;
            PP2));

        $rule = $declarations[0];

        Assert::instanceOf($rule, RuleDeclaration::class);
        Assert::instanceOf($rule->reducer, CodeReducer::class);
        Assert::same($rule->reducer->code, <<<'PHP'
            if ($children === []) {
                return null;
            }

            // A brace "}" written inside a comment
            return \implode('}', $children);
            PHP);
    }

    public function testDeclarationOffsets(): void
    {
        $declarations = new PP2Parser()->parse(StringSource::createFromString(<<<'PP2'
            %token T_A a

            A : <T_A> ;
            PP2));

        [$token, $rule] = $declarations;

        Assert::instanceOf($token, TokenDeclaration::class);
        Assert::instanceOf($rule, RuleDeclaration::class);

        Assert::same($token->offset, 0);
        Assert::same($rule->offset, 14);
    }

    public function testStatementOffsets(): void
    {
        $declarations = new PP2Parser()->parse(StringSource::createFromString('A : <T_A> ::T_B:: ;'));

        $rule = $declarations[0];

        Assert::instanceOf($rule, RuleDeclaration::class);
        Assert::instanceOf($rule->body, Concatenation::class);

        Assert::same($rule->body->statements[0]->offset, 5);
        Assert::same($rule->body->statements[1]->offset, 12);
    }

    public function testSyntaxError(): void
    {
        Expect::exception(UnexpectedTokenException::class);

        new PP2Parser()->parse(StringSource::createFromString('A : ;'));
    }
}
