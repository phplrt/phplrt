<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

interface ChannelInterface
{
    /**
     * Gets a channel name.
     *
     * A "channel" is a specific tag that is specific to a particular
     * token or group of tokens.
     *
     * For example, all whitespace-like characters can be marked with the
     * {@see Channel::Hidden} channel, implying that this set of tokens
     * should be excluded from the lexer's output.
     *
     * Note: The `$value` name of the property is due to
     *       problems in PHP in supporting enumerations.
     *
     * @var non-empty-string
     */
    public string $value {
        get;
    }
}
