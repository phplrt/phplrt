<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Internal;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Lexer\Token\UserDefinedChannel;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Lexer
 */
final readonly class ChannelLoader
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

        foreach ($channels as $channelName) {
            if (isset($result[$channelName])) {
                continue;
            }

            $result[$channelName] = Channel::tryFrom($channelName)
                ?? new UserDefinedChannel($channelName);
        }

        return $result;
    }
}
