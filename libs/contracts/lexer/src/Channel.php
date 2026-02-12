<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The basic set of token channels.
 *
 * If you need your own channel, you can define your own instance
 * by implementing the {@see ChannelInterface} interface.
 */
enum Channel: string implements ChannelInterface
{
    /**
     * Hidden tokens channel name.
     *
     * All tokens in this channel should be ignored.
     */
    case Hidden = 'hidden';

    /**
     * A channel marking a token as unrecognized
     */
    case Unknown = 'unknown';

    /**
     * This token's type corresponds to a terminal token and can only be
     * singular in the entire token stream.
     */
    case EndOfInput = 'eoi';
}
