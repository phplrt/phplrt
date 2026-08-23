<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The basic set of the token channels.
 *
 * An implementation MAY mark a token with a channel of its own, which MUST
 * NOT be a member of this set.
 */
enum Channel implements ChannelInterface
{
    /**
     * The channel of the significant tokens.
     */
    case Default;

    /**
     * The channel of the tokens that MUST be ignored.
     */
    case Hidden;

    /**
     * The channel of the tokens that have not been recognized.
     */
    case Unknown;

    /**
     * The channel of the terminal token.
     *
     * A stream MUST contain at most one token of this channel.
     */
    case EndOfInput;

    /**
     * The channel of the significant tokens.
     */
    public const self DEFAULT = self::Default;

    /**
     * Returns every channel of this set, indexed by its own name.
     *
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
