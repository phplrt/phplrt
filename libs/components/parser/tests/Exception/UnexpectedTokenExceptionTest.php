<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Exception;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\Token;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Tests\TestCase;
use Phplrt\Source\Source;
use Phplrt\Source\VirtualFile;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser')]
final class UnexpectedTokenExceptionTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private const string SOURCE = "first line\nsecond line\nthird line";

    #[TestDox('The source the error occurred in is available')]
    public function testSource(): void
    {
        $source = new Source(self::SOURCE);

        $exception = UnexpectedTokenException::fromToken($source, $this->createToken());

        self::assertSame($source, $exception->source);
        self::assertSame(18, $exception->token->offset);
    }

    #[TestDox('The error is printed along with the fragment of the source code')]
    public function testStringRepresentation(): void
    {
        $exception = UnexpectedTokenException::fromToken(new Source(self::SOURCE), $this->createToken());

        self::assertStringStartsWith(<<<'OUT'
            error[UnexpectedTokenException]: Syntax error, unexpected "line" (T_WORD)
              |
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT, (string) $exception);
    }

    #[TestDox('The error occurred in a file is printed along with the name of that file')]
    public function testStringRepresentationOfAFile(): void
    {
        $source = new VirtualFile('/app/example.pp2', self::SOURCE);

        $exception = UnexpectedTokenException::fromToken($source, $this->createToken());

        self::assertStringStartsWith(<<<'OUT'
            error[UnexpectedTokenException]: Syntax error, unexpected "line" (T_WORD)
             --> /app/example.pp2:2:8
              |
            1 | first line
            2 | second line
              |        ^^^^
            3 | third line
            OUT, (string) $exception);
    }

    #[TestDox('The error occurred outside the source code is printed without a fragment')]
    public function testStringRepresentationOfAnEmptySource(): void
    {
        $exception = UnexpectedTokenException::fromToken(
            new Source(''),
            new Token(0, null, Channel::EndOfInput, '', 0),
        );

        self::assertStringStartsWith(<<<'OUT'
            error[UnexpectedTokenException]: Syntax error, unexpected end of input
              |
            1 |
              | ^
            OUT, (string) $exception);
    }

    private function createToken(): TokenInterface
    {
        return new Token(0, 'T_WORD', Channel::Default, 'line', 18);
    }
}
