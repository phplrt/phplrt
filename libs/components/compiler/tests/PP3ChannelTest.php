<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class PP3ChannelTest extends TestCase
{
    /**
     * A documentation comment is read the way any other token is and then left
     * beside the reading, so only the rule asking for it ever sees it.
     */
    private const DOCUMENTED = <<<'PP3'
        %skip  T_WHITESPACE   \s++
        %token T_DOC_COMMENT  /\*.*?\*/  -> channel(comment)
        %token T_NAME         \w++

        Names -> { return $children; }
          : Name()+
          ;

        Name -> {
            $children = (array) $children;

            return \implode(' ', \array_map(static fn($token) => $token->value, $children));
        }
          : <T_DOC_COMMENT>? <T_NAME>
          ;
        PP3;

    public function testCommentIsGivenToTheRuleAskingForIt(): void
    {
        $parser = self::compile(self::DOCUMENTED);

        Assert::same(
            $parser->parse(StringSource::createFromString('/** doc */ Foo')),
            ['/** doc */ Foo'],
        );
    }

    public function testRuleIsNotBrokenByTheAbsenceOfTheComment(): void
    {
        $parser = self::compile(self::DOCUMENTED);

        Assert::same($parser->parse(StringSource::createFromString('Foo')), ['Foo']);
    }

    public function testEveryCommentBelongsToTheNameAfterIt(): void
    {
        $parser = self::compile(self::DOCUMENTED);

        Assert::same(
            $parser->parse(StringSource::createFromString('/** a */ Foo /** b */ Bar')),
            ['/** a */ Foo', '/** b */ Bar'],
        );
    }

    /**
     * The comment is read out of the stream rather than looked up beside it,
     * so an optional one takes the first of several the way it would anywhere
     * else.
     */
    public function testOptionalCommentTakesTheFirstOfSeveral(): void
    {
        $parser = self::compile(self::DOCUMENTED);

        Assert::same(
            $parser->parse(StringSource::createFromString('/** a */ /** b */ Foo')),
            ['/** a */ Foo'],
        );
    }

    /**
     * The whole point of a channel: a rule saying nothing about the token is
     * not broken by it, wherever it stands.
     */
    public function testGrammarSayingNothingAboutTheTokenIsNotBrokenByIt(): void
    {
        $parser = self::compile(<<<'PP3'
            %skip  T_WHITESPACE   \s++
            %token T_DOC_COMMENT  /\*.*?\*/  -> channel(comment)
            %token T_NAME         \w++

            Names -> {
                return \implode(' ', \array_map(
                    static fn($token) => $token->value,
                    (array) $children,
                ));
            }
              : <T_NAME>+
              ;
            PP3);

        foreach ([
            '/* a */ Foo Bar',
            'Foo /* a */ Bar',
            'Foo Bar /* a */',
        ] as $source) {
            Assert::same($parser->parse(StringSource::createFromString($source)), 'Foo Bar');
        }
    }

    public function testTokensOfSeveralChannelsAreToldApart(): void
    {
        $parser = self::compile(<<<'PP3'
            %skip  T_WHITESPACE   \s++
            %token T_DOC_COMMENT  /\*\*.*?\*/  -> channel(doc)
            %token T_NOTE         //[^\n]*+    -> channel(note)
            %token T_NAME         \w++

            Name -> {
                return \implode('|', \array_map(
                    static fn($token) => \trim($token->value),
                    (array) $children,
                ));
            }
              : <T_NOTE>? <T_DOC_COMMENT>? <T_NAME>
              ;
            PP3);

        Assert::same(
            $parser->parse(StringSource::createFromString("// note\n/** doc */ Foo")),
            '// note|/** doc */|Foo',
        );
    }

    /**
     * A skipped token is never built, so it reaches no rule no matter what one
     * says about it - which is what tells a channel from "%skip".
     */
    public function testSkippedTokenIsStillOutOfReach(): void
    {
        Expect::exception(ParserCompilerException::class)
        ->withMessageContaining('does not reach the parser');

        self::compile(<<<'PP3'
            %skip  T_DOC_COMMENT  /\*.*?\*/
            %token T_NAME         \w++

            Name : <T_DOC_COMMENT>? <T_NAME> ;
            PP3);
    }

    private static function compile(string $grammar): ParserInterface
    {
        $compiler = new Compiler();
        $compiler->load(StringSource::createFromString($grammar));

        return $compiler->getParser();
    }
}
