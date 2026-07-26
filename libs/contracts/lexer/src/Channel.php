<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The basic set of token channels.
 *
 * If you need your own channel, you can define your own instance
 * by implementing the {@see ChannelInterface} interface.
 */
enum Channel implements ChannelInterface
{
    /**
     * Default token channel name.
     */
    case Default;

    /**
     * Hidden tokens channel name.
     *
     * All tokens in this channel should be ignored.
     */
    case Hidden;

    /**
     * A channel marking a token as unrecognized
     */
    case Unknown;

    /**
     * This token's type corresponds to a terminal token and can only be
     * singular in the entire token stream.
     */
    case EndOfInput;

    public const self DEFAULT = self::Default;

    /**
     * @return non-empty-array<non-empty-string, Channel>
     */
    public static function names(): array
    {
        $result = [];

        foreach (self::cases() as $case) {
            $result[$case->name] = $case;
        }

        return $result;
    }
}
