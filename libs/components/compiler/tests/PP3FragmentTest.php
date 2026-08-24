<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\CompilerResult;
use Phplrt\Compiler\Exception\DuplicateFragmentException;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\StringSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/compiler')]
final class PP3FragmentTest extends TestCase
{
    #[TestDox('A piece declared by a grammar is written into the expressions referring to it')]
    public function testFragmentIsWrittenIntoExpression(): void
    {
        $result = $this->compile(<<<'PP3'
            %fragment DIGIT  [0-9]
            %fragment EXP    [eE][+-]?(?&DIGIT)++

            %token T_NUMBER  (?&DIGIT)++(\.(?&DIGIT)++)?(?&EXP)?

            Number : <T_NUMBER> ;
            PP3);

        self::assertSame(
            '(?:[0-9])++(\.(?:[0-9])++)?(?:[eE][+-]?(?:[0-9])++)?',
            self::regexOf($result, 'T_NUMBER'),
        );
    }

    #[TestDox('A piece is written into the expression whatever the order they are declared in')]
    public function testFragmentIsDeclaredAfterUse(): void
    {
        $result = $this->compile(<<<'PP3'
            %token T_NUMBER  (?&DIGIT)++
            %fragment DIGIT  [0-9]

            Number : <T_NUMBER> ;
            PP3);

        self::assertSame('(?:[0-9])++', self::regexOf($result, 'T_NUMBER'));
    }

    #[TestDox('A piece is written into the expressions of every state')]
    public function testFragmentReachesEveryState(): void
    {
        $result = $this->compile(<<<'PP3'
            %fragment WORD  [a-z]++

            %token        T_QUOTE_OPEN   "  -> state(string)
            %token string:T_TEXT         (?&WORD)
            %token string:T_QUOTE_CLOSE  "  -> exit()

            Str : <T_QUOTE_OPEN> ;
            PP3);

        $state = $result->lexer->lexers['string'];

        self::assertInstanceOf(LexerBuilderResult::class, $state);
        self::assertSame('(?:[a-z]++)', self::findRegex($state->tokens, 'T_TEXT'));
    }

    #[TestDox('A piece is written into the expression of a token belonging to every state')]
    public function testFragmentReachesSharedToken(): void
    {
        $result = $this->compile(<<<'PP3'
            %fragment SPACE  [\x20\t]

            %skip  *:T_WHITESPACE  (?&SPACE)++
            %token        T_QUOTE_OPEN   "  -> state(string)
            %token string:T_QUOTE_CLOSE  "  -> exit()

            Str : <T_QUOTE_OPEN> ;
            PP3);

        $state = $result->lexer->lexers['string'];

        self::assertInstanceOf(LexerBuilderResult::class, $state);
        self::assertSame('(?:[\x20\t])++', self::regexOf($result, 'T_WHITESPACE'));
        self::assertSame('(?:[\x20\t])++', self::findRegex($state->tokens, 'T_WHITESPACE'));
    }

    #[TestDox('The lexer reads what the expressions the pieces are written into recognize')]
    public function testGrammarReadsWhatFragmentsDescribe(): void
    {
        $parser = new Compiler()
            ->load(StringSource::createFromString(<<<'PP3'
                %fragment DIGIT  [0-9]
                %fragment EXP    [eE][+-]?(?&DIGIT)++

                %skip  T_WHITESPACE  \s++
                %token T_NUMBER      (?&DIGIT)++(\.(?&DIGIT)++)?(?&EXP)?

                Number -> { return (float) $children->value; }
                  : <T_NUMBER>
                  ;
                PP3))
        ->getParser();

        self::assertSame(35000000000.0, $parser->parse(StringSource::createFromString(' 3.5e10 ')));
    }

    #[TestDox('A piece that has not been declared is reported')]
    public function testUnknownFragmentIsReported(): void
    {
        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessageIsOrContains(
            'refers to the "DIGT" fragment, which has not been declared',
        );

        $this->compile(<<<'PP3'
            %fragment DIGIT  [0-9]
            %token T_NUMBER  (?&DIGT)++

            Number : <T_NUMBER> ;
            PP3);
    }

    #[TestDox('A piece written of itself is reported')]
    public function testRecursiveFragmentIsReported(): void
    {
        $this->expectException(CompilationFailedException::class);
        $this->expectExceptionMessageIsOrContains('fragment is written of itself');

        $this->compile(<<<'PP3'
            %fragment A  (?&B)
            %fragment B  (?&A)

            %token T_TEXT  (?&A)

            Text : <T_TEXT> ;
            PP3);
    }

    #[TestDox('A piece declared twice is reported')]
    public function testDuplicateFragmentIsReported(): void
    {
        $this->expectException(DuplicateFragmentException::class);
        $this->expectExceptionMessageIsOrContains('The "A" fragment has already been declared');

        $this->compile(<<<'PP3'
            %fragment A  [a-z]
            %fragment A  [0-9]

            %token T_TEXT  (?&A)

            Text : <T_TEXT> ;
            PP3);
    }

    #[TestDox('A piece belonging to a state is reported')]
    public function testFragmentWithStateIsReported(): void
    {
        $this->expectException(UnexpectedTokenException::class);
        $this->expectExceptionMessageIsOrContains('unexpected "string:"');

        $this->compile(<<<'PP3'
            %fragment string:A  [a-z]

            %token T_TEXT  (?&A)

            Text : <T_TEXT> ;
            PP3);
    }

    #[TestDox('A piece doing something to the reading is reported')]
    public function testFragmentWithActionIsReported(): void
    {
        $this->expectException(UnexpectedTokenException::class);
        $this->expectExceptionMessageIsOrContains('unexpected "-> exit()"');

        $this->compile(<<<'PP3'
            %fragment A  [a-z]  -> exit()

            %token T_TEXT  (?&A)

            Text : <T_TEXT> ;
            PP3);
    }

    #[TestDox('A piece becomes no token of its own')]
    public function testFragmentIsNotAToken(): void
    {
        $result = $this->compile(<<<'PP3'
            %fragment DIGIT  [0-9]
            %token T_NUMBER  (?&DIGIT)++

            Number : <T_NUMBER> ;
            PP3);

        self::assertSame(['T_NUMBER'], \array_values($result->lexer->names));
    }

    private function compile(string $grammar): CompilerResult
    {
        return new Compiler()
            ->load(StringSource::createFromString($grammar))
        ->build();
    }

    private static function regexOf(CompilerResult $result, string $name): ?string
    {
        return self::findRegex($result->lexer->tokens, $name);
    }

    private static function findRegex(array $tokens, string $name): ?string
    {
        foreach ($tokens as $token) {
            if ($token instanceof RegexTokenDefinition && $token->name === $name) {
                return $token->regex;
            }
        }

        return null;
    }
}
