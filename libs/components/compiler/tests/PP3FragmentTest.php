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
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class PP3FragmentTest extends TestCase
{
    public function testFragmentIsWrittenIntoExpression(): void
    {
        $result = $this->compile(<<<'PP3'
            %fragment DIGIT  [0-9]
            %fragment EXP    [eE][+-]?(?&DIGIT)++

            %token T_NUMBER  (?&DIGIT)++(\.(?&DIGIT)++)?(?&EXP)?

            Number : <T_NUMBER> ;
            PP3);

        Assert::same(self::regexOf($result, 'T_NUMBER'), '(?:[0-9])++(\.(?:[0-9])++)?(?:[eE][+-]?(?:[0-9])++)?');
    }

    public function testFragmentIsDeclaredAfterUse(): void
    {
        $result = $this->compile(<<<'PP3'
            %token T_NUMBER  (?&DIGIT)++
            %fragment DIGIT  [0-9]

            Number : <T_NUMBER> ;
            PP3);

        Assert::same(self::regexOf($result, 'T_NUMBER'), '(?:[0-9])++');
    }

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

        Assert::instanceOf($state, LexerBuilderResult::class);
        Assert::same(self::findRegex($state->tokens, 'T_TEXT'), '(?:[a-z]++)');
    }

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

        Assert::instanceOf($state, LexerBuilderResult::class);
        Assert::same(self::regexOf($result, 'T_WHITESPACE'), '(?:[\x20\t])++');
        Assert::same(self::findRegex($state->tokens, 'T_WHITESPACE'), '(?:[\x20\t])++');
    }

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

        Assert::same($parser->parse(StringSource::createFromString(' 3.5e10 ')), 35000000000.0);
    }

    public function testUnknownFragmentIsReported(): void
    {
        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining(
            'refers to the "DIGT" fragment, which has not been declared',
        );

        $this->compile(<<<'PP3'
            %fragment DIGIT  [0-9]
            %token T_NUMBER  (?&DIGT)++

            Number : <T_NUMBER> ;
            PP3);
    }

    public function testRecursiveFragmentIsReported(): void
    {
        Expect::exception(CompilationFailedException::class)
        ->withMessageContaining('fragment is written of itself');

        $this->compile(<<<'PP3'
            %fragment A  (?&B)
            %fragment B  (?&A)

            %token T_TEXT  (?&A)

            Text : <T_TEXT> ;
            PP3);
    }

    public function testDuplicateFragmentIsReported(): void
    {
        Expect::exception(DuplicateFragmentException::class)
        ->withMessageContaining('The "A" fragment has already been declared');

        $this->compile(<<<'PP3'
            %fragment A  [a-z]
            %fragment A  [0-9]

            %token T_TEXT  (?&A)

            Text : <T_TEXT> ;
            PP3);
    }

    public function testFragmentWithStateIsReported(): void
    {
        Expect::exception(UnexpectedTokenException::class)
        ->withMessageContaining('unexpected "string:"');

        $this->compile(<<<'PP3'
            %fragment string:A  [a-z]

            %token T_TEXT  (?&A)

            Text : <T_TEXT> ;
            PP3);
    }

    public function testFragmentWithActionIsReported(): void
    {
        Expect::exception(UnexpectedTokenException::class)
        ->withMessageContaining('unexpected "-> exit()"');

        $this->compile(<<<'PP3'
            %fragment A  [a-z]  -> exit()

            %token T_TEXT  (?&A)

            Text : <T_TEXT> ;
            PP3);
    }

    public function testFragmentIsNotAToken(): void
    {
        $result = $this->compile(<<<'PP3'
            %fragment DIGIT  [0-9]
            %token T_NUMBER  (?&DIGIT)++

            Number : <T_NUMBER> ;
            PP3);

        Assert::same(\array_values($result->lexer->names), ['T_NUMBER']);
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
