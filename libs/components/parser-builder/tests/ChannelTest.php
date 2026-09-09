<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

/**
 * A token of a channel of its own is a token the parser may step over, the way
 * a skipped one is stepped over by the lexer: it reaches the reading, and only
 * the rule asking for it by name ever reads it.
 */
#[Group('phplrt/parser-compiler')]
#[Test]
final class ChannelTest extends TestCase
{
    private static function createLexerWithChannel(): LexerBuilder
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('\s++', 'T_WHITESPACE')
            ->hide();
        $lexer->addPattern('/\*.*?\*/', 'T_COMMENT')
            ->setChannel('comment');
        $lexer->addPattern('\d++', 'T_NUMBER');
        $lexer->addValue('+', 'T_PLUS');

        return $lexer;
    }

    /**
     * @param \Closure(ParserBuilder): RuleDefinition $make
     */
    private static function createParserFor(\Closure $make): ParserInterface
    {
        $lexer = self::createLexerWithChannel();

        $parser = new ParserBuilder();
        $parser->setInitialRule($make($parser));

        return $parser->build($lexer->build())
            ->toParser(self::createLexer($lexer));
    }

    /**
     * Root : <T_NUMBER>+ ;
     */
    private static function createBlindParser(): ParserInterface
    {
        return self::createParserFor(static fn(ParserBuilder $p): RuleDefinition => $p->addRepetition(
            $p->addTokenReference('T_NUMBER'),
            min: 1,
            name: 'Root',
        ));
    }

    /**
     * Root : <T_COMMENT>? <T_NUMBER> ;
     */
    private static function createReadingParser(): ParserInterface
    {
        return self::createParserFor(static fn(ParserBuilder $p): RuleDefinition => $p->addConcatenation([
            $p->addOptional($p->addTokenReference('T_COMMENT')),
            $p->addTokenReference('T_NUMBER'),
        ], 'Root'));
    }

    public function testTokenOfAChannelIsSteppedOver(): void
    {
        $parser = self::createBlindParser();

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('1 /* here */ 2'))),
            ['1', '2'],
        );
    }

    public function testTokenOfAChannelIsSteppedOverBeforeTheFirstOne(): void
    {
        $parser = self::createBlindParser();

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('/* here */ 1'))),
            ['1'],
        );
    }

    /**
     * A token of a channel standing at the end of the source is not a part of
     * the source left unread.
     */
    public function testTokenOfAChannelIsSteppedOverBeforeTheEndOfInput(): void
    {
        $parser = self::createBlindParser();

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('1 /* here */'))),
            ['1'],
        );
    }

    public function testRuleAskingForTheTokenIsGivenIt(): void
    {
        $parser = self::createReadingParser();

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('/* here */ 1'))),
            ['/* here */', '1'],
        );
    }

    public function testRuleAskingForTheTokenIsNotBrokenByItsAbsence(): void
    {
        $parser = self::createReadingParser();

        Assert::same(self::collectValues($parser->parse(StringSource::createFromString('1'))), ['1']);
    }

    /**
     * The token is read out of the stream rather than looked up beside it, so
     * the rules are written of it the way they are written of any other token.
     */
    public function testTokensOfAChannelAreReadInTheOrderTheyAreWritten(): void
    {
        $parser = self::createParserFor(static fn(ParserBuilder $p): RuleDefinition => $p->addConcatenation([
            $p->addRepetition($p->addTokenReference('T_COMMENT')),
            $p->addTokenReference('T_NUMBER'),
        ], 'Root'));

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('/* a */ /* b */ 1'))),
            ['/* a */', '/* b */', '1'],
        );
    }

    public function testOptionalTokenTakesTheFirstOfSeveral(): void
    {
        $parser = self::createReadingParser();

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('/* a */ /* b */ 1'))),
            ['/* a */', '1'],
        );
    }

    public function testTokenOfAChannelIsReadWithoutBeingKept(): void
    {
        $parser = self::createParserFor(static fn(ParserBuilder $p): RuleDefinition => $p->addConcatenation([
            $p->addTokenReference('T_COMMENT')
                ->skip(),
            $p->addTokenReference('T_NUMBER'),
        ], 'Root'));

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('/* here */ 1'))),
            ['1'],
        );
    }

    public function testRuleAskingForAMissingTokenIsNotRecognized(): void
    {
        $parser = self::createParserFor(static fn(ParserBuilder $p): RuleDefinition => $p->addConcatenation([
            $p->addTokenReference('T_COMMENT'),
            $p->addTokenReference('T_NUMBER'),
        ], 'Root'));

        Expect::exception(UnexpectedTokenException::class);

        $parser->parse(StringSource::createFromString('1'));
    }

    /**
     * Root : Sum() ;
     * Sum  : <T_NUMBER> <T_PLUS> <T_NUMBER> ;
     *
     * The tokens a rule may begin with are the ones it reads, and a token of a
     * channel is not among them, so a rule standing behind one is entered all
     * the same instead of being rejected by the table.
     */
    public function testRuleIsEnteredThroughATokenOfAChannel(): void
    {
        $parser = self::createParserFor(static function (ParserBuilder $p): RuleDefinition {
            $sum = $p->addConcatenation([
                $p->addTokenReference('T_NUMBER'),
                $p->addTokenReference('T_PLUS'),
                $p->addTokenReference('T_NUMBER'),
            ], 'Sum');

            return $p->addConcatenation([$sum], 'Root');
        });

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('/* here */ 1 + 2'))),
            ['1', '+', '2'],
        );
    }

    /**
     * A failure never moves the reading, so what a rejected rule has stepped
     * over is given back to the one tried after it.
     */
    public function testRejectedAlternativeLeavesNothingBehind(): void
    {
        $parser = self::createParserFor(static fn(ParserBuilder $p): RuleDefinition => $p->addAlternation([
            $p->addConcatenation([
                $p->addTokenReference('T_PLUS'),
                $p->addTokenReference('T_NUMBER'),
            ]),
            $p->addConcatenation([
                $p->addTokenReference('T_COMMENT'),
                $p->addTokenReference('T_NUMBER'),
            ]),
        ], 'Root'));

        Assert::same(
            self::collectValues($parser->parse(StringSource::createFromString('/* here */ 1'))),
            ['/* here */', '1'],
        );
    }

    public function testGrammar(): void
    {
        $lexer = self::createLexerWithChannel();

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addConcatenation([
            $parser->addTokenReference('T_COMMENT'),
            $parser->addTokenReference('T_NUMBER'),
        ], 'Root'));

        // A token of a channel is an ordinary terminal: what tells it apart is
        // the channel the token itself carries, so the grammar says nothing
        // about it at all
        Assert::same(self::describe($parser->build($lexer->build())), [
            '0: Concatenation(1, 2)',
            '1: Lexeme(1, keep)',
            '2: Lexeme(2, keep)',
        ]);
    }
}
