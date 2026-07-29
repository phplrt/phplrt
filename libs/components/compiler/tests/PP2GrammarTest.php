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
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/compiler')]
final class PP2GrammarTest extends TestCase
{
    #[TestDox('A grammar file without declarations is read into nothing')]
    public function testEmptyGrammar(): void
    {
        self::assertSame([], self::describe(''));
    }

    #[TestDox('A grammar file of comments alone is read into nothing')]
    public function testCommentsOnlyGrammar(): void
    {
        self::assertSame([], self::describe(<<<'PP2'
            // A single line comment
            /*
             * And a multiline one
             */
            PP2));
    }

    #[TestDox('A token declaration is read into its name and its pattern')]
    public function testTokenDeclaration(): void
    {
        self::assertSame([
            '%token T_NUMBER \d++',
        ], self::describe('%token T_NUMBER \d++'));
    }

    #[TestDox('A token declared using "%skip" never reaches the parser')]
    public function testSkippedTokenDeclaration(): void
    {
        self::assertSame([
            '%skip T_WHITESPACE \s++',
        ], self::describe('%skip T_WHITESPACE \s++'));
    }

    #[TestDox('A token declaration is read along with the states it mentions')]
    public function testTokenDeclarationStates(): void
    {
        self::assertSame([
            '%token T_QUOTE " -> string',
            '%token string:T_CHAR [^"]++',
            '%token string:T_END " -> default',
        ], self::describe(<<<'PP2'
            %token        T_QUOTE  "        -> string
            %token string:T_CHAR   [^"]++
            %token string:T_END    "        -> default
            PP2));
    }

    #[TestDox('A pattern spelled like a name is read as a pattern')]
    public function testTokenPatternSpelledLikeName(): void
    {
        self::assertSame([
            '%token T_IF if',
        ], self::describe('%token T_IF if'));
    }

    #[TestDox('A pattern beginning with the two slashes of a comment is read as a pattern')]
    public function testTokenPatternBeginningWithComment(): void
    {
        self::assertSame([
            '%skip T_COMMENT //[^\n]*',
        ], self::describe('%skip T_COMMENT //[^\n]*'));
    }

    #[TestDox('A comment written after a declaration is not a part of it')]
    public function testCommentAfterDeclaration(): void
    {
        self::assertSame([
            '%token T_NUMBER \d++',
            '%pragma root Sum',
            '%include grammar/lexemes',
        ], self::describe(<<<'PP2'
            %token   T_NUMBER  \d++              // A number
            %pragma  root      Sum               // Where the analysis starts
            %include grammar/lexemes             // The tokens
            PP2));
    }

    #[TestDox('A pragma declaration is read into its name and its value')]
    public function testPragmaDeclaration(): void
    {
        self::assertSame([
            '%pragma parser.root Sum',
        ], self::describe('%pragma parser.root Sum'));
    }

    #[TestDox('An include declaration is read into the pathname it mentions')]
    public function testIncludeDeclaration(): void
    {
        self::assertSame([
            '%include grammar/lexemes',
        ], self::describe('%include grammar/lexemes'));
    }

    #[TestDox('A rule is separated from what it recognizes by any of the three separators')]
    public function testRuleSeparators(): void
    {
        self::assertSame([
            'A : <T_NUMBER> ;',
            'B : <T_NUMBER> ;',
            'C : <T_NUMBER> ;',
        ], self::describe(<<<'PP2'
            A  :   <T_NUMBER> ;
            B  =   <T_NUMBER> ;
            C  ::= <T_NUMBER> ;
            PP2));
    }

    #[TestDox('The semicolon ending a rule may be omitted')]
    public function testRuleWithoutSemicolon(): void
    {
        self::assertSame([
            'A : <T_NUMBER> ;',
            'B : <T_NAME> ;',
        ], self::describe(<<<'PP2'
            A : <T_NUMBER>
            B : <T_NAME>
            PP2));
    }

    #[TestDox('A rule declared with the "#" prefix is kept in the syntax tree')]
    public function testKeptRule(): void
    {
        self::assertSame([
            '#A : <T_NUMBER> ;',
        ], self::describe('#A : <T_NUMBER>;'));
    }

    #[TestDox('A token reference is read along with the way it is written')]
    public function testTokenReferences(): void
    {
        self::assertSame([
            'A : (<T_NUMBER> ::T_COMMA::) ;',
        ], self::describe('A : <T_NUMBER> ::T_COMMA:: ;'));
    }

    #[TestDox('A pattern written inside a rule is read as a statement of its own')]
    public function testInlinePattern(): void
    {
        self::assertSame([
            'A : "\+" ;',
        ], self::describe('A : "\+" ;'));
    }

    #[TestDox('The escaping of the quotes surrounding an inline pattern is undone')]
    public function testInlinePatternQuotes(): void
    {
        $declarations = new PP2Parser()->parse(new Source('A : "\"" ;'));

        $rule = $declarations[0];

        self::assertInstanceOf(RuleDeclaration::class, $rule);
        self::assertInstanceOf(InlinePattern::class, $rule->body);
        self::assertSame('"', $rule->body->pattern);
    }

    #[TestDox('The alternatives of a rule are read in the order they are written')]
    public function testAlternation(): void
    {
        self::assertSame([
            'A : (<T_A> | <T_B> | <T_C>) ;',
        ], self::describe('A : <T_A> | <T_B> | <T_C> ;'));
    }

    #[TestDox('A group is read as the statement it surrounds')]
    public function testGroup(): void
    {
        self::assertSame([
            'A : (<T_A> (<T_B> | <T_C>)) ;',
        ], self::describe('A : <T_A> ( <T_B> | <T_C> ) ;'));
    }

    #[TestDox('A statement of a single element is read without a production of its own')]
    public function testSingleStatement(): void
    {
        self::assertSame([
            'A : <T_A> ;',
        ], self::describe('A : ( ( <T_A> ) ) ;'));
    }

    #[TestDox('Every quantifier is read into the number of repetitions it allows')]
    public function testQuantifiers(): void
    {
        self::assertSame([
            'A : (<T_A>{0,1} <T_A>{1,} <T_A>{0,} <T_A>{2,5} <T_A>{2,} <T_A>{0,5} <T_A>{7,7}) ;',
        ], self::describe('A : <T_A>? <T_A>+ <T_A>* <T_A>{2,5} <T_A>{2,} <T_A>{,5} <T_A>{7} ;'));
    }

    #[TestDox('A quantifier applies to the group it follows')]
    public function testQuantifiedGroup(): void
    {
        self::assertSame([
            'A : (<T_A> ::T_COMMA:: <T_A>){0,} ;',
        ], self::describe('A : ( <T_A> ::T_COMMA:: <T_A> )* ;'));
    }

    #[TestDox('A reducer written as a class name is read as it is written')]
    public function testClassReducer(): void
    {
        self::assertSame([
            'A -> \App\Node\SumNode : <T_A> ;',
        ], self::describe('A -> \App\Node\SumNode : <T_A> ;'));
    }

    #[TestDox('A reducer written as code is read up to the brace closing it')]
    public function testCodeReducer(): void
    {
        $declarations = new PP2Parser()->parse(new Source(<<<'PP2'
            A -> {
                if ($children === []) {
                    return null;
                }

                // A brace "}" written inside a comment
                return \implode('}', $children);
            } : <T_A> ;
            PP2));

        $rule = $declarations[0];

        self::assertInstanceOf(RuleDeclaration::class, $rule);
        self::assertInstanceOf(CodeReducer::class, $rule->reducer);
        self::assertSame(<<<'PHP'
            if ($children === []) {
                return null;
            }

            // A brace "}" written inside a comment
            return \implode('}', $children);
            PHP, $rule->reducer->code);
    }

    #[TestDox('The position a declaration starts at is where it is written')]
    public function testDeclarationOffsets(): void
    {
        $declarations = new PP2Parser()->parse(new Source(<<<'PP2'
            %token T_A a

            A : <T_A> ;
            PP2));

        [$token, $rule] = $declarations;

        self::assertInstanceOf(TokenDeclaration::class, $token);
        self::assertInstanceOf(RuleDeclaration::class, $rule);

        self::assertSame(0, $token->offset);
        self::assertSame(14, $rule->offset);
    }

    #[TestDox('The position of a statement is where the statement itself is written')]
    public function testStatementOffsets(): void
    {
        $declarations = new PP2Parser()->parse(new Source('A : <T_A> ::T_B:: ;'));

        $rule = $declarations[0];

        self::assertInstanceOf(RuleDeclaration::class, $rule);
        self::assertInstanceOf(Concatenation::class, $rule->body);

        self::assertSame(5, $rule->body->statements[0]->offset);
        self::assertSame(12, $rule->body->statements[1]->offset);
    }

    #[TestDox('A grammar that cannot be recognized is reported as a syntax error')]
    public function testSyntaxError(): void
    {
        $this->expectException(UnexpectedTokenException::class);

        new PP2Parser()->parse(new Source('A : ;'));
    }
}
