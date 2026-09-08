<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class PP3AdjacencyTest extends TestCase
{
    private const NAMESPACES = <<<'PP3'
        %skip  T_WHITESPACE     \s++
        %token T_NAME           \w++
        %token T_NS_SEPARATOR   \\

        Namespaces -> { return $children; }
          : Namespace()+
          ;

        Namespace -> {
            $result = '';

            foreach ((array) $children as $token) {
                $result .= $token->value;
            }

            return $result;
        }
          : ::T_NS_SEPARATOR:: ~ Relative()
          | Relative()
          ;

        Relative : (<T_NAME> ~ ::T_NS_SEPARATOR:: ~)* <T_NAME> ;
        PP3;

    public function testASpaceEndsAName(): void
    {
        $parser = self::compile(self::NAMESPACES);

        Assert::same(
            $parser->parse(StringSource::createFromString('Some\Any \Ololo\Trololo')),
            ['SomeAny', 'OloloTrololo'],
        );
    }

    public function testAQualifiedNameIsReadAsAWhole(): void
    {
        $parser = self::compile(self::NAMESPACES);

        Assert::same($parser->parse(StringSource::createFromString('Some\Any\More')), ['SomeAnyMore']);
    }

    public function testALeadingSeparatorBelongsToTheNameAfterIt(): void
    {
        $parser = self::compile(self::NAMESPACES);

        Assert::same($parser->parse(StringSource::createFromString('\A \B')), ['A', 'B']);
    }

    public function testASeparatorWrittenApartIsNotRead(): void
    {
        $parser = self::compile(self::NAMESPACES);

        Expect::exception(UnexpectedTokenException::class);

        $parser->parse(StringSource::createFromString('A \ B'));
    }

    public function testACommentIsWrittenInBetween(): void
    {
        $parser = self::compile(<<<'PP3'
            %skip  T_COMMENT  /\*.*?\*/
            %token T_NAME     \w++
            %token T_DOT      \.

            Pair -> { return 'ok'; }
              : <T_NAME> ~ ::T_DOT:: ~ <T_NAME>
              ;
            PP3);

        Assert::same($parser->parse(StringSource::createFromString('a.b')), 'ok');

        Expect::exception(UnexpectedTokenException::class);

        $parser->parse(StringSource::createFromString('a/* here */.b'));
    }

    public function testAdjacencyAfterAnOptionalStatementIsReported(): void
    {
        Expect::exception(ParserCompilerException::class)
            ->withMessageContaining('may be recognized without reading one');

        self::compile(<<<'PP3'
            %token T_NAME  \w++
            %token T_DOT   \.

            Pair : <T_NAME>? ~ ::T_DOT:: ;
            PP3);
    }

    public function testAdjacencyOpeningARuleIsReported(): void
    {
        Expect::exception(ParserCompilerException::class)
            ->withMessageContaining('it cannot open the sequence');

        self::compile(<<<'PP3'
            %token T_NAME  \w++
            %token T_DOT   \.

            Pair : ~ <T_NAME> ::T_DOT:: ;
            PP3);
    }

    public function testAReferenceToASkippedTokenNamesTheWaysOut(): void
    {
        Expect::exception(ParserCompilerException::class)
            ->withMessageContaining('Write "~" between the statements');

        self::compile(<<<'PP3'
            %skip  T_WHITESPACE  \s++
            %token T_NAME        \w++

            Pair : <T_NAME> ::T_WHITESPACE:: <T_NAME> ;
            PP3);
    }

    public function testAdjacencyIsWrittenDownAsSourceCode(): void
    {
        $code = (string) new Compiler()
            ->load(StringSource::createFromString(<<<'PP3'
                %token T_NAME  \w++
                %token T_DOT   \.

                Pair : <T_NAME> ~ ::T_DOT:: ;
                PP3))
            ->generate();

        Assert::string($code)
            ->contains('\Phplrt\Parser\Grammar\Adjacency(true)');
    }

    private static function compile(string $grammar): ParserInterface
    {
        $compiler = new Compiler();
        $compiler->load(StringSource::createFromString($grammar));

        return $compiler->getParser();
    }
}
