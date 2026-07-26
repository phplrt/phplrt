<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests\Token;

use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Tests\TestCase;
use Phplrt\Lexer\Token\EndOfInputToken;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer')]
final class TokenTest extends TestCase
{
    #[TestDox('The terminal token uses a system (negative) identifier')]
    public function testTerminalTokenUsesASystemIdentifier(): void
    {
        $token = new EndOfInputToken(0);

        self::assertLessThan(0, $token->id);
    }

    #[TestDox('The terminal token is empty and belongs to the end of input channel')]
    public function testTerminalTokenIsEmpty(): void
    {
        $token = new EndOfInputToken(42);

        self::assertSame('', $token->value);
        self::assertSame(42, $token->offset);
        self::assertSame(Channel::EndOfInput, $token->channel);
    }

    #[TestDox('A significant token uses a non-negative identifier')]
    public function testSignificantTokensUseNonNegativeIdentifiers(): void
    {
        $lexer = self::createNamesLexer();
        $source = 'one two';

        foreach ($lexer->lex($source) as $token) {
            if ($token->channel === Channel::EndOfInput) {
                continue;
            }

            self::assertGreaterThanOrEqual(0, $token->id, \sprintf(
                'A significant token %s is expected to use a non-negative identifier',
                $token->name ?? '#' . $token->id,
            ));
        }
    }

    #[TestDox('A token offset is never less than the minimal one')]
    public function testOffsetIsNeverLessThanTheMinimalOne(): void
    {
        $lexer = self::createNamesLexer();
        $source = 'word';

        foreach ($lexer->lex($source) as $token) {
            self::assertGreaterThanOrEqual(TokenInterface::MIN_OFFSET, $token->offset);
        }
    }

    private static function createNamesLexer(): LexerInterface
    {
        return self::lexer(static function (LexerBuilder $lexer): void {
            $lexer->addPattern('\s++', 'T_WHITESPACE')->setHidden();
            $lexer->addPattern('[a-z]++', 'T_NAME');
        });
    }
}
