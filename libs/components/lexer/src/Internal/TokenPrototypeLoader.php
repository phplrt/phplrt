<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Internal;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Lexer\Token\Token;

/**
 * Prepares a sample of every token the lexer can recognize.
 *
 * An identifier, a name and a channel are the same for every occurrence of a
 * token, so instead of looking all three up per token while reading, we build
 * one ready-made token per identifier here and later just clone it and fill in
 * what was actually found in the source.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Lexer
 */
final class TokenPrototypeLoader
{
    /**
     * @param array<int, non-empty-string> $channels a map of token ID and its
     *        channel name
     * @param array<int, non-empty-string> $names a map of token ID and its
     *        original name
     * @return array<int, Token>
     */
    public static function load(array $channels, array $names): array
    {
        $instances = ChannelLoader::load($channels);
        $result = [];

        foreach ($names as $id => $name) {
            $result[$id] = self::createPrototype($id, $name, $instances[$id] ?? Channel::DEFAULT);
        }

        // A token may be given a channel but no name at all, and it's still a
        // token the lexer reads, so it needs a prototype too.
        foreach ($instances as $id => $channel) {
            $result[$id] ??= self::createPrototype($id, null, $channel);
        }

        return $result;
    }

    /**
     * What a token the configuration says nothing about looks like: no name and
     * the default channel.
     */
    public static function createFallbackPrototype(): Token
    {
        return self::createPrototype(0, null, Channel::DEFAULT);
    }

    /**
     * @param non-empty-string|null $name
     */
    private static function createPrototype(int $id, ?string $name, ChannelInterface $channel): Token
    {
        return new Token(
            id: $id,
            name: $name,
            channel: $channel,
            value: '',
        );
    }
}
