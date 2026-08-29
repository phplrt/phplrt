<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Lexer\Builder\Definition\ValueTokenDefinition;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Builder\ParserBuilderResult;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser-compiler')]
#[Test]
final class CompilerPassTest extends TestCase
{
    public function testEmptyGrammar(): void
    {
        Expect::exception(ParserCompilerException::class)
        ->withMessage('The grammar of the parser contains no rules');

        self::compile(new ParserBuilder());
    }

    public function testRuleNameDuplication(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_NUMBER', 'Number'),
            $parser->addTokenReference('T_PLUS', 'Number'),
        ]));

        Expect::exception(CompilationFailedException::class)
        ->withMessage('Rule name of Number = <name is "T_PLUS"> is not unique');

        self::compile($parser);
    }

    public function testUnresolvableReference(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([$parser->addRuleReference('Missing')], 'Root'));

        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('refers to the rule named "Missing", which has not been defined');

        self::compile($parser);
    }

    public function testReferenceAsInitialRule(): void
    {
        $parser = new ParserBuilder();
        $parser->addTokenReference('T_PLUS');
        $parser->addTokenReference('T_NUMBER', 'Number');
        $parser->setInitialRule($parser->addRuleReference('Number'));

        $result = self::compile($parser);

        Assert::same($result->constants, ['Number' => 0]);
        Assert::same($result->initial, 0);
    }

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

        Assert::same(self::describe($result), [
            '0: Concatenation(1, 2, 1)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ]);
        Assert::same($result->constants, ['Root' => 0, 'Number' => 1]);
    }

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

        Assert::same(self::describe($result), [
            '0: Concatenation(1, 2, 1)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, skip)',
        ]);
    }

    public function testUnknownTokenName(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference('T_UNKNOWN', 'Root'));

        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('refers to the token, which is not recognized by the lexer');

        self::compile($parser);
    }

    public function testUnknownTokenId(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference(42, 'Root'));

        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('refers to the token, which is not recognized by the lexer');

        self::compile($parser);
    }

    public function testForeignTokenDefinitionReference(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference(new ValueTokenDefinition('+'), 'Root'));

        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('refers to the token, which is not recognized by the lexer');

        self::compile($parser);
    }

    public function testHiddenTokenReference(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference('T_WHITESPACE', 'Root'));

        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('refers to the hidden token');

        self::compile($parser);
    }

    public function testEmptyProduction(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([], 'Root'));

        Expect::exception(CompilationFailedException::class)
        ->withMessage('Rule Root = () must refer to at least one rule');

        self::compile($parser);
    }

    public function testInvalidRepetition(): void
    {
        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addRepetition(
            rule: $parser->addTokenReference('T_NUMBER'),
            max: 2,
            min: 5,
            name: 'Root',
        ));

        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('cannot be repeated from 5 to 2 times');

        self::compile($parser);
    }

    public function testDirectLeftRecursion(): void
    {
        $parser = new ParserBuilder();
        $expression = $parser->addConcatenation(name: 'Expression');
        $expression->setRules([$expression, $parser->addTokenReference('T_NUMBER')]);
        $parser->setInitialRule($expression);

        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('is left recursive: Expression -> Expression');

        self::compile($parser);
    }

    public function testIndirectLeftRecursion(): void
    {
        $parser = new ParserBuilder();
        $number = $parser->addTokenReference('T_NUMBER');
        $first = $parser->addConcatenation(name: 'First');
        $second = $parser->addConcatenation(name: 'Second');

        $first->setRules([$second, $number]);
        $second->setRules([$first, $number]);

        $parser->setInitialRule($first);

        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('is left recursive: First -> Second -> First');

        self::compile($parser);
    }

    public function testLeftRecursionBehindNullableRule(): void
    {
        $parser = new ParserBuilder();
        $sign = $parser->addOptional($parser->addTokenReference('T_PLUS'), 'Sign');
        $expression = $parser->addConcatenation(name: 'Expression');

        $expression->setRules([$sign, $expression]);

        $parser->setInitialRule($expression);

        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('is left recursive: Expression -> Expression');

        self::compile($parser);
    }

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

        Assert::count(self::compile($parser)->grammar, 3);
    }

    private static function compile(ParserBuilder $parser): ParserBuilderResult
    {
        return $parser->build(self::createLexerBuilder()->build());
    }
}
