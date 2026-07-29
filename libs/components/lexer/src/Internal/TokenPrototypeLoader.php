<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Internal;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Lexer\Token\Token;

/**
 * Prepares what every token the lexer recognizes looks like.
 *
 * Everything a token is known by before the source is read (its identifier, its
 * name and the channel it belongs to) is the same for every occurrence of it,
 * so it is written down once and the reading only adds what it has found in
 * the source.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Lexer
 */
final class TokenPrototypeLoader
{
    /**
     * Gets the lexer configuration and prepares a token of every identifier it
     * mentions.
     *
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

        // A token the lexer has been given a channel but no name for is
        // recognized just as well, so it is prepared too
        foreach ($instances as $id => $channel) {
            $result[$id] ??= self::createPrototype($id, null, $channel);
        }

        return $result;
    }

    /**
     * Prepares the token every identifier the configuration does not mention
     * looks like: nameless and belonging to the default channel.
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
