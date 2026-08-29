<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Exception;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\Token;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Tests\TestCase;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser')]
#[Test]
final class UnexpectedTokenExceptionTest extends TestCase
{
    private const string SOURCE = "first line\nsecond line\nthird line";

    public function testSource(): void
    {
        $source = StringSource::createFromString(self::SOURCE);

        $exception = UnexpectedTokenException::becauseUnexpectedTokenProduced($source, $this->createToken());

        Assert::same($exception->source, $source);
        Assert::same($exception->token->offset, 18);
    }

    public function testStringRepresentation(): void
    {
        $exception = UnexpectedTokenException::becauseUnexpectedTokenProduced(StringSource::createFromString(self::SOURCE), $this->createToken());

        Assert::true(\str_starts_with((string) $exception, <<<'OUT'
            error[UnexpectedTokenException]: Syntax error, unexpected "line" (T_WORD)
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT));
    }

    public function testStringRepresentationOfAFile(): void
    {
        $source = VirtualSource::createFromString('/app/example.pp2', self::SOURCE);

        $exception = UnexpectedTokenException::becauseUnexpectedTokenProduced($source, $this->createToken());

        Assert::true(\str_starts_with((string) $exception, <<<'OUT'
            error[UnexpectedTokenException]: Syntax error, unexpected "line" (T_WORD)
             --> /app/example.pp2:2:8
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT));
    }

    public function testStringRepresentationOfAnEmptySource(): void
    {
        $exception = UnexpectedTokenException::becauseUnexpectedTokenProduced(
            StringSource::createEmpty(),
            new Token(0, null, Channel::EndOfInput, '', 0),
        );

        Assert::true(\str_starts_with((string) $exception, <<<'OUT'
            error[UnexpectedTokenException]: Syntax error, unexpected end of input
            1 |
              | ^
            OUT));
    }

    private function createToken(): TokenInterface
    {
        return new Token(0, 'T_WORD', Channel::Default, 'line', 18);
    }
}
