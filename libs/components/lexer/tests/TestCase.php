<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Builder\Transformer\RuntimeLexerTransformer;
use Phplrt\Lexer\Lexer;
use Phplrt\Lexer\Token\TokenEmbedding;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected static function lexer(
        callable $definition,
        iterable $skip = Lexer::DEFAULT_SKIP_CHANNELS,
    ): LexerInterface {
        $builder = new LexerBuilder();

        $definition($builder);

        return new RuntimeLexerTransformer($skip)
            ->transform($builder->build());
    }

    protected static function describe(iterable $tokens): array
    {
        $result = [];

        foreach ($tokens as $token) {
            $result[] = \sprintf(
                '%s(%s)@%d',
                $token->name ?? '#' . $token->id,
                $token->value,
                $token->offset,
            );
        }

        return $result;
    }

    protected static function assertTokensMatchSource(string $source, iterable $tokens): void
    {
        foreach ($tokens as $token) {
            self::assertSame(
                \substr($source, $token->offset, \strlen($token->value)),
                $token->value,
                \sprintf(
                    'Token #%d is expected to be located at offset %d of the source',
                    $token->id,
                    $token->offset,
                ),
            );
        }
    }

    protected static function assertTokensCoverSource(string $source, iterable $tokens): void
    {
        $expected = 0;

        foreach ($tokens as $token) {
            self::assertSame($expected, $token->offset, \sprintf(
                'Token #%d is expected to continue the previous one at offset %d',
                $token->id,
                $expected,
            ));

            $expected = $token->offset + $token->size;
        }

        self::assertSame(\strlen($source), $expected, 'The source is expected to be read in full');
    }

    protected static function describeTree(iterable $tokens, string $indent = ''): array
    {
        $result = [];

        foreach ($tokens as $token) {
            $result[] = $indent . \sprintf(
                '%s(%s)@%d',
                $token->name ?? '#' . $token->id,
                $token->value,
                $token->offset,
            );

            if ($token instanceof TokenEmbedding) {
                foreach (self::describeTree($token->children, $indent . '    ') as $child) {
                    $result[] = $child;
                }
            }
        }

        return $result;
    }

    protected static function assertTerminatedStream(string $source, iterable $tokens): void
    {
        $tokens = \iterator_to_array($tokens, false);

        self::assertNotSame([], $tokens, 'A token stream is expected to never be empty');

        $terminal = [];

        foreach ($tokens as $index => $token) {
            if ($token->channel === Channel::EndOfInput) {
                $terminal[] = $index;
            }
        }

        self::assertCount(1, $terminal, 'The "end of input" token is expected to be singular');
        self::assertSame(\count($tokens) - 1, $terminal[0], 'The "end of input" token is expected to be the last one');
        self::assertSame(\strlen($source), $tokens[$terminal[0]]->offset, 'The "end of input" token is expected to be located at the end of the source');
        self::assertSame('', $tokens[$terminal[0]]->value, 'The "end of input" token is expected to be empty');
    }
}
