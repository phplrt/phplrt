<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests\Functional;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Exception\UnexpectedStateException;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Multistate;

class MultistateLexerTest extends TestCase
{
    public function testNoStatesError(): void
    {
        $this->expectException(UnexpectedStateException::class);
        $this->expectExceptionMessage('No state defined for the selected multistate lexer');

        [...(new Multistate([]))->lex('Hello World!')];
    }

    public function testUnknownStateTransitionError(): void
    {
        $this->expectException(UnexpectedStateException::class);
        $this->expectExceptionMessage('Unrecognized token state #unknown_state');

        $result = (new Multistate([new Lexer(['word' => '\w+'])]))
            ->when('word', 0, 'unknown_state')
            ->lex('example')
        ;

        [...$result];
    }

    public function testUnknownInitialStateError(): void
    {
        $this->expectException(UnexpectedStateException::class);
        $this->expectExceptionMessage('Unrecognized token state #unknown_state');

        $result = (new Multistate([new Lexer(['word' => '\w+'])], [], 'unknown_state'))
            ->lex('example');

        [...$result];
    }

    public function testSimpleMultistateExpression(): void
    {
        $result = (new Multistate([
            'string' => [
                'escaped_quote' => '\\\\"',
                'quote' => '"',
                'char' => '\w',
            ],
            'default' => [
                'quote' => '"'
            ],
        ]))
            ->startsWith('default')
            ->when('quote', 'default', 'string')
            ->when('quote', 'string', 'default')
            ->lex('"a\"b"')
        ;

        $this->assertSame(
            ['quote', 'char', 'escaped_quote', 'char', 'quote', 'T_EOI'],
            \array_map(fn(TokenInterface $token): string => $token->getName(), [...$result])
        );
    }
}
