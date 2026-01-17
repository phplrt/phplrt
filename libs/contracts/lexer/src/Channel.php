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
     * The main channel for any non-special tokens.
     */
    case Main;

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

    /**
     * A default token channel
     */
    public const self DEFAULT = self::Main;
}
