<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Exception\CompilationFailedException;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Builder\Transformer\RuntimeLexerTransformer;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Core\Exception\SkipTest;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

/**
 * An expression means what the flags of its lexer say, so a token is checked
 * and its subgroups are counted with the very same flags it is read with.
 */
#[Group('phplrt/lexer-builder')]
#[Test]
final class PatternFlagsTest extends TestCase
{
    /**
     * @param non-empty-string $pattern
     * @param list<RegexModifier> $enabled
     * @param list<RegexModifier> $disabled
     */
    private static function readFirst(
        string $pattern,
        string $source,
        array $enabled = [],
        array $disabled = [],
    ): TokenInterface {
        $builder = new LexerBuilder();
        $builder->addPattern($pattern, 'T_TOKEN');

        foreach ($enabled as $flag) {
            $builder->enable($flag);
        }

        foreach ($disabled as $flag) {
            $builder->disable($flag);
        }

        $lexer = (new RuntimeLexerTransformer())
            ->transform($builder->build());

        foreach ($lexer->lex(StringSource::createFromString($source)) as $token) {
            return $token;
        }

        Assert::fail('A token stream is expected to never be empty');
    }

    private static function requireNoAutoCapture(): void
    {
        if (\PHP_VERSION_ID < 80200) {
            throw new SkipTest('The "n" modifier is only known to PHP 8.2 and above');
        }
    }

    public function testUnnamedSubgroupsOfNoAutoCapturePatternCaptureNothing(): void
    {
        self::requireNoAutoCapture();

        $token = self::readFirst('(a)(b)', 'ab', [RegexModifier::NoAutoCapture]);

        Assert::same($token->value, 'ab');
        Assert::same($token->captures, []);
    }

    public function testNamedSubgroupOfNoAutoCapturePatternIsCaptured(): void
    {
        self::requireNoAutoCapture();

        Assert::same(self::readFirst('(?<x>a)(b)', 'ab', [RegexModifier::NoAutoCapture])->captures, ['a']);
    }

    public function testSubgroupInCommentOfExtendedPatternCapturesNothing(): void
    {
        $token = self::readFirst('a#(b)', 'a', [RegexModifier::Extended]);

        Assert::same($token->value, 'a');
        Assert::same($token->captures, []);
    }

    public function testCommentOfExtendedPatternIsValid(): void
    {
        Assert::same(self::readFirst('a#(', 'a', [RegexModifier::Extended])->value, 'a');
    }

    public function testDuplicateNamesAllowedByTheFlagAreValid(): void
    {
        Assert::same(self::readFirst('(?<n>a)|(?<n>b)', 'b', [RegexModifier::InfoJChanged])->value, 'b');
    }

    public function testPatternInvalidWithoutUtf8IsReportedOnBuild(): void
    {
        Expect::exception(CompilationFailedException::class)
            ->withMessageContaining('/\x{100}/ (T_TOKEN)');

        self::readFirst('\x{100}', 'a', disabled: [RegexModifier::Utf8]);
    }

    public function testPatternValidWithUtf8IsRead(): void
    {
        Assert::same(self::readFirst('\x{100}', "\u{100}")->value, "\u{100}");
    }
}
