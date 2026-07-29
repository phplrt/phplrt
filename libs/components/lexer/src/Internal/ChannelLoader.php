<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Internal;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\UserDefinedChannel;

/**
 * Turns the channel names of a lexer configuration into channel instances.
 *
 * Tokens on the same channel share one instance of it, so the same channel is
 * always the same object no matter which token you reach it through.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Lexer
 */
final class ChannelLoader
{
    /**
     * Gets the lexer configuration and initializes the mapping of tokens to channels.
     *
     * @param array<int, non-empty-string> $channels
     * @return array<int, ChannelInterface>
     */
    public static function load(array $channels): array
    {
        $result = [];
        $instances = self::createChannelInstances($channels);

        foreach ($channels as $tokenId => $channelName) {
            $result[$tokenId] = $instances[$channelName];
        }

        return $result;
    }

    /**
     * Gets the lexer configuration and initializes channel instances.
     *
     * @param array<int, non-empty-string> $channels
     * @return array<non-empty-string, ChannelInterface>
     */
    private static function createChannelInstances(array $channels): array
    {
        $result = [];

        $builtin = Channel::names();

        foreach ($channels as $channelName) {
            if (isset($result[$channelName])) {
                continue;
            }

            $result[$channelName] = $builtin[$channelName] ?? new UserDefinedChannel($channelName);
        }

        return $result;
    }
}
