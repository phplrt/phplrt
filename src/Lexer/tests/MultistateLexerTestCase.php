<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Exception\UnexpectedStateException;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Multistate;

class MultistateLexerTestCase extends TestCase
{
    /**
     * @return void
     * @throws RuntimeExceptionInterface
     */
    public function testNoStatesError(): void
    {
        $this->expectException(UnexpectedStateException::class);
        $this->expectExceptionMessage('No state defined for the selected multistate lexer');

        [...(new Multistate([]))->lex('Hello World!')];
    }

    /**
     * @return void
     * @throws RuntimeExceptionInterface
     */
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

    /**
     * @return void
     * @throws RuntimeExceptionInterface
     */
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
                'quote'         => '"',
                'char'          => '\w',
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
            \array_map(fn (TokenInterface $token): string => $token->getName(), [...$result])
        );
    }
}
