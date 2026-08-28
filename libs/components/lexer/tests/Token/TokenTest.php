<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests\Token;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Tests\TestCase;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer')]
#[Test]
final class TokenTest extends TestCase
{
    public function testTerminalTokenUsesASystemIdentifier(): void
    {
        $token = new EndOfInputToken(0);

        Assert::numeric($token->id)->lessThan(0);
    }

    public function testTerminalTokenIsEmpty(): void
    {
        $token = new EndOfInputToken(42);

        Assert::same($token->value, '');
        Assert::same($token->offset, 42);
        Assert::same($token->channel, Channel::EndOfInput);
    }

    public function testSignificantTokensUseNonNegativeIdentifiers(): void
    {
        $lexer = self::createNamesLexer();
        $source = 'one two';

        foreach ($lexer->lex(StringSource::createFromString($source)) as $token) {
            if ($token->channel === Channel::EndOfInput) {
                continue;
            }

            Assert::numeric($token->id)->greaterThanOrEqual(0, \sprintf(
                'A significant token %s is expected to use a non-negative identifier',
                $token->name ?? '#' . $token->id,
            ));
        }
    }

    public function testOffsetIsNeverLessThanTheMinimalOne(): void
    {
        $lexer = self::createNamesLexer();
        $source = 'word';

        foreach ($lexer->lex(StringSource::createFromString($source)) as $token) {
            Assert::numeric($token->offset)->greaterThanOrEqual(TokenInterface::MIN_OFFSET);
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
