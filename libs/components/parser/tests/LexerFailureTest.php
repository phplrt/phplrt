<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Exception\LexerException;
use Phplrt\Lexer\Exception\LexerSourceException;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;
use Phplrt\Lexer\Token\UnknownToken;
use Phplrt\Parser\Exception\LexicalErrorException;
use Phplrt\Parser\Exception\ParserException;
use Phplrt\Parser\Exception\ParserSourceException;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Parser;
use Phplrt\Parser\Tests\Stub\FailingLexer;
use Phplrt\Source\Exception\FileNotReadableException;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser')]
#[Test]
final class LexerFailureTest extends TestCase
{
    public function testLexicalErrorIsReportedAsParserError(): void
    {
        $source = StringSource::createFromString('1 ? 2');
        $token = new UnknownToken('?', 2);
        $error = UnrecognizedTokenException::becauseInputIsUnrecognized($source, $token);

        $actual = self::catch(new FailingLexer($error), $source);

        Assert::instanceOf($actual, LexicalErrorException::class);
        Assert::same($actual->getMessage(), $error->getMessage());
        Assert::same($actual->source, $source);
        Assert::same($actual->token, $token);
        Assert::same($actual->getPrevious(), $error);
    }

    public function testUnreadableSourceIsReportedAsParserError(): void
    {
        $reason = FileNotReadableException::becauseFileNotReadable('/app/example.php');

        $actual = self::catch(new FailingLexer(LexerSourceException::becauseSourceIsNotReadable($reason)));

        Assert::instanceOf($actual, ParserSourceException::class);
        Assert::same($actual->getMessage(), 'The source code cannot be read: ' . $reason->getMessage());
        Assert::same($actual->getPrevious(), $reason);
    }

    public function testAnyOtherLexerErrorIsReportedAsParserError(): void
    {
        $error = new LexerException('Lexer failure');

        $actual = self::catch(new FailingLexer($error));

        Assert::same($actual::class, ParserException::class);
        Assert::same($actual->getMessage(), 'Lexer failure');
        Assert::same($actual->getPrevious(), $error);
    }

    public function testErrorNotOfTheLexerIsNotWrapped(): void
    {
        $error = new \LogicException('Unforeseen failure');

        Assert::same(self::catch(new FailingLexer($error)), $error);
    }

    public function testAnalysisReportsLexerErrorAsParserError(): void
    {
        $parser = self::createParser(new FailingLexer(new LexerException('Lexer failure')));

        try {
            $parser->analyze(StringSource::createFromString('1'));
        } catch (ParserException $e) {
            Assert::same($e->getMessage(), 'Lexer failure');

            return;
        }

        Assert::fail('The analysis has not failed');
    }

    private static function catch(FailingLexer $lexer, ?ReadableInterface $source = null): \Throwable
    {
        try {
            self::createParser($lexer)->parse($source ?? StringSource::createFromString('1'));
        } catch (\Throwable $e) {
            return $e;
        }

        Assert::fail('The parser has not failed');
    }

    private static function createParser(FailingLexer $lexer): Parser
    {
        return new Parser(
            lexer: $lexer,
            grammar: [new Lexeme(0)],
            initial: 0,
        );
    }
}
