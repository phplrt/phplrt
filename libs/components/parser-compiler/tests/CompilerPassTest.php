<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Tests;

use Phplrt\Compiler\Lexer\Definition\ValueTokenDefinition;
use Phplrt\Compiler\Parser\Exception\CompilationFailedException;
use Phplrt\Compiler\Parser\Exception\ParserCompilerException;
use Phplrt\Compiler\Parser\ParserBuilder;
use Phplrt\Compiler\Parser\ParserBuilderResult;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser-compiler')]
final class CompilerPassTest extends TestCase
{
    #[TestDox('A grammar without rules cannot be compiled')]
    public function testEmptyGrammar(): void
    {
        $this->expectException(ParserCompilerException::class);
        $this->expectExceptionMessage('The grammar of the parser contains no rules');

        self::compile(new ParserBuilder());
    }

    #[TestDox('Two rules cannot share the same name')]
    public function testRuleNameDuplication(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER', 'Number'),
            $parser->addTokenReference('T_PLUS', 'Number'),
        ]));

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('Rule name of Number = <name is "T_PLUS"> is not unique');

        self::compile($parser);
    }

    #[TestDox('A reference to an undefined rule is reported')]
    public function testUnresolvableReference(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([$parser->addRuleReference('Missing')], 'Root'));

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('refers to the rule named "Missing", which has not been defined');

        self::compile($parser);
    }

    #[TestDox('A reference may be marked as the rule the analysis starts at')]
    public function testReferenceAsInitialRule(): void
    {
        $parser = new ParserBuilder();
        $parser->addTokenReference('T_PLUS');
        $parser->addTokenReference('T_NUMBER', 'Number');
        $parser->setInitialRule($parser->addRuleReference('Number'));

        $result = self::compile($parser);

        self::assertSame(['Number' => 0], $result->constants);
        self::assertSame(0, $result->initial);
    }

    #[TestDox('A reference does not reach the grammar as a rule of its own')]
    public function testReferenceIsNotCompiled(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addRuleReference('Number'),
            $parser->addTokenReference('T_PLUS')->skip(),
            $parser->addRuleReference('Number'),
        ], 'Root'));
        $parser->addTokenReference('T_NUMBER', 'Number');

        $result = self::compile($parser);

        self::assertSame([
            '0: Concatenation(1, 2, 1)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ], self::describe($result));
        self::assertSame(['Root' => 0, 'Number' => 1], $result->constants);
    }

    #[TestDox('A reference may point at the rule definition instead of its name')]
    public function testReferenceByDefinition(): void
    {
        $parser = new ParserBuilder();
        $number = $parser->addTokenReference('T_NUMBER', 'Number');

        $parser->setInitialRule($parser->addConcatenation([
            $parser->addRuleReference($number),
            $parser->addTokenReference('T_PLUS')->skip(),
            $parser->addRuleReference($number),
        ], 'Root'));

        $result = self::compile($parser);

        self::assertSame([
            '0: Concatenation(1, 2, 1)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ], self::describe($result));
    }

    #[TestDox('A rule referring to a token the lexer does not recognize is reported')]
    public function testUnknownTokenName(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference('T_UNKNOWN', 'Root'));

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('refers to the token, which is not recognized by the lexer');

        self::compile($parser);
    }

    #[TestDox('A rule referring to a token identifier the lexer does not use is reported')]
    public function testUnknownTokenId(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference(42, 'Root'));

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('refers to the token, which is not recognized by the lexer');

        self::compile($parser);
    }

    #[TestDox('A rule referring to a token of another lexer is reported')]
    public function testForeignTokenDefinitionReference(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference(new ValueTokenDefinition('+'), 'Root'));

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('refers to the token, which is not recognized by the lexer');

        self::compile($parser);
    }

    #[TestDox('A rule referring to a hidden token is reported')]
    public function testHiddenTokenReference(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference('T_WHITESPACE', 'Root'));

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('refers to the hidden token');

        self::compile($parser);
    }

    #[TestDox('A production without inner rules is reported')]
    public function testEmptyProduction(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([], 'Root'));

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('Rule Root = () must refer to at least one rule');

        self::compile($parser);
    }

    #[TestDox('A repetition that cannot be recognized at all is reported')]
    public function testInvalidRepetition(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addRepetition(
            rule: $parser->addTokenReference('T_NUMBER'),
            max: 2,
            min: 5,
            name: 'Root',
        ));

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('cannot be repeated from 5 to 2 times');

        self::compile($parser);
    }

    #[TestDox('A rule referring to itself before it recognizes a token is reported')]
    public function testDirectLeftRecursion(): void
    {
        $parser = new ParserBuilder();
        $expression = $parser->addConcatenation(name: 'Expression');
        $expression->setRules([$expression, $parser->addTokenReference('T_NUMBER')]);
        $parser->setInitialRule($expression);

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('is left recursive: Expression -> Expression');

        self::compile($parser);
    }

    #[TestDox('A cycle of rules that recognize nothing is reported')]
    public function testIndirectLeftRecursion(): void
    {
        $parser = new ParserBuilder();
        $number = $parser->addTokenReference('T_NUMBER');
        $first = $parser->addConcatenation(name: 'First');
        $second = $parser->addConcatenation(name: 'Second');

        $first->setRules([$second, $number]);
        $second->setRules([$first, $number]);

        $parser->setInitialRule($first);

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('is left recursive: First -> Second -> First');

        self::compile($parser);
    }

    #[TestDox('A rule referring to itself behind an optional one is reported')]
    public function testLeftRecursionBehindNullableRule(): void
    {
        $parser = new ParserBuilder();
        $sign = $parser->addOptional($parser->addTokenReference('T_PLUS'), 'Sign');
        $expression = $parser->addConcatenation(name: 'Expression');

        $expression->setRules([$sign, $expression]);

        $parser->setInitialRule($expression);

        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessage('is left recursive: Expression -> Expression');

        self::compile($parser);
    }

    #[TestDox('A rule referring to itself behind a token is recognizable')]
    public function testRecursionBehindToken(): void
    {
        $parser = new ParserBuilder();
        $group = $parser->addConcatenation(name: 'Group');

        $group->setRules([
            $parser->addTokenReference('T_PLUS')->skip(),
            $group,
            $parser->addTokenReference('T_MINUS')->skip(),
        ]);

        $parser->setInitialRule($group);

        self::assertCount(3, self::compile($parser)->grammar);
    }

    private static function compile(ParserBuilder $parser): ParserBuilderResult
    {
        return $parser->build(self::createLexerBuilder()->build());
    }
}
