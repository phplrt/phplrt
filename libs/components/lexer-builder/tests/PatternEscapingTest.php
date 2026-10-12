<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\Internal\PatternFormatter;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Builder\Transformer\RuntimeLexerTransformer;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

/**
 * A pattern is written the way it would be written between the delimiters,
 * so whatever it escapes on its own must reach PCRE escaped exactly once.
 */
#[Group('phplrt/lexer-builder')]
#[Test]
final class PatternEscapingTest extends TestCase
{
    /**
     * @param non-empty-string $pattern
     * @param list<RegexModifier> $flags
     * @return list<string>
     */
    private static function read(string $pattern, string $source, array $flags = []): array
    {
        $builder = new LexerBuilder();
        $builder->addPattern($pattern, 'T_TOKEN');
        $builder->addPattern('[a-w]++', 'T_NAME');

        foreach ($flags as $flag) {
            $builder->enable($flag);
        }

        $lexer = (new RuntimeLexerTransformer())
            ->transform($builder->build());

        $result = [];

        foreach ($lexer->lex(StringSource::createFromString($source)) as $token) {
            $result[] = \sprintf('%s(%s)', $token->name ?? $token->channel->name, $token->value);
        }

        return $result;
    }

    private static function readFirst(string $pattern, string $source): TokenInterface
    {
        $builder = new LexerBuilder();
        $builder->addPattern($pattern, 'T_TOKEN');

        $lexer = (new RuntimeLexerTransformer())
            ->transform($builder->build());

        foreach ($lexer->lex(StringSource::createFromString($source)) as $token) {
            return $token;
        }

        Assert::fail('A token stream is expected to never be empty');
    }

    public function testEscapedDelimiterIsRead(): void
    {
        Assert::same(self::read('\/', 'a/b'), ['T_NAME(a)', 'T_TOKEN(/)', 'T_NAME(b)', 'EndOfInput()']);
    }

    public function testUnescapedDelimiterIsRead(): void
    {
        Assert::same(self::read('/', 'a/b'), ['T_NAME(a)', 'T_TOKEN(/)', 'T_NAME(b)', 'EndOfInput()']);
    }

    public function testEscapedHashIsRead(): void
    {
        Assert::same(self::read('\#', 'a#b'), ['T_NAME(a)', 'T_TOKEN(#)', 'T_NAME(b)', 'EndOfInput()']);
    }

    public function testUnescapedHashIsRead(): void
    {
        Assert::same(self::read('#', 'a#b'), ['T_NAME(a)', 'T_TOKEN(#)', 'T_NAME(b)', 'EndOfInput()']);
    }

    public function testEscapedBackslashBeforeDelimiterIsRead(): void
    {
        Assert::same(self::read('\\\\/', 'a\\/b'), ['T_NAME(a)', 'T_TOKEN(\\/)', 'T_NAME(b)', 'EndOfInput()']);
    }

    public function testDelimiterInsideCharacterClassIsRead(): void
    {
        Assert::same(self::read('[\/#]++', 'a/#b'), ['T_NAME(a)', 'T_TOKEN(/#)', 'T_NAME(b)', 'EndOfInput()']);
    }

    public function testSubgroupAfterEscapedDelimiterIsCaptured(): void
    {
        Assert::same(self::readFirst('\/(\w)', '/x')->captures, ['x']);
    }

    public function testDelimiterInsideQuotedTextIsRead(): void
    {
        Assert::same(self::read('\Q/#\E', 'a/#b'), ['T_NAME(a)', 'T_TOKEN(/#)', 'T_NAME(b)', 'EndOfInput()']);
    }

    public function testBackslashInsideQuotedTextMeansItself(): void
    {
        Assert::same(self::read('\Q\/\E', 'a\\/b'), ['T_NAME(a)', 'T_TOKEN(\\/)', 'T_NAME(b)', 'EndOfInput()']);
    }

    public function testUnterminatedQuotedTextDoesNotSwallowOtherTokens(): void
    {
        Assert::same(self::read('\Q/', 'a/b'), ['T_NAME(a)', 'T_TOKEN(/)', 'T_NAME(b)', 'EndOfInput()']);
    }

    public function testEscapedPatternIsEscapedOnceInTheMessage(): void
    {
        Expect::exception(CompilationFailedException::class)
            ->withMessageContaining('Missing closing parenthesis in /a\/(/ (T_TOKEN) token definition');

        self::read('a\/(', '');
    }

    public function testPatternEndingWithBackslashIsReported(): void
    {
        Expect::exception(CompilationFailedException::class);

        self::read('a\\', '');
    }

    public function testInvalidPatternAfterEscapedDelimiterIsReportedOnBuild(): void
    {
        Expect::exception(CompilationFailedException::class)
            ->withMessageContaining('Quantifier does not follow a repeatable item');

        self::read('\/+*', '');
    }

    public function testSubgroupBeforeUnterminatedQuotedTextIsCaptured(): void
    {
        Assert::same(self::readFirst('(a)\Qb', 'ab')->captures, ['a']);
    }

    public function testUnterminatedQuotedTextOfExtendedPatternEndsWithTheToken(): void
    {
        Assert::same(self::read('\Q/', 'a/b', [RegexModifier::Extended]), [
            'T_NAME(a)',
            'T_TOKEN(/)',
            'T_NAME(b)',
            'EndOfInput()',
        ]);
    }

    public function testHashIsReadAsItIs(): void
    {
        Assert::same(self::read('[#]', 'a#b'), ['T_NAME(a)', 'T_TOKEN(#)', 'T_NAME(b)', 'EndOfInput()']);
    }

    public function testCommentOfExtendedPatternDoesNotSwallowOtherTokens(): void
    {
        Assert::same(self::read('x # the letter', 'axb', [RegexModifier::Extended]), [
            'T_NAME(a)',
            'T_TOKEN(x)',
            'T_NAME(b)',
            'EndOfInput()',
        ]);
    }

    public function testEscapedHashOfExtendedPatternIsRead(): void
    {
        Assert::same(self::read('\#', 'a#b', [RegexModifier::Extended]), [
            'T_NAME(a)',
            'T_TOKEN(#)',
            'T_NAME(b)',
            'EndOfInput()',
        ]);
    }

    public function testEscaperOnlyEscapesTheDelimiter(): void
    {
        Assert::same(PatternFormatter::escapeDelimiter('a/b\/c\\\\/d', '/'), 'a\/b\/c\\\\\/d');
        Assert::same(PatternFormatter::escapeDelimiter('\#[#]', '/'), '\#[#]');
        Assert::same(PatternFormatter::escapeDelimiter('\Q/\E/', '/'), '\Q\E\/\Q\E\/');
        Assert::same(PatternFormatter::escapeDelimiter('\Q/', '/'), '\Q\E\/\Q\E');
    }

    public function testEscapingTwiceChangesNothing(): void
    {
        foreach (['a/b', 'a\/b', '\\\\/', '\Q/\E', '\Q/', '[/]'] as $pattern) {
            $once = PatternFormatter::escapeDelimiter($pattern, '/');

            Assert::same(PatternFormatter::escapeDelimiter($once, '/'), $once);
        }
    }
}
