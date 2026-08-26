<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * A tag a token or a group of tokens is marked with, so that these tokens can
 * be told apart from the rest of a stream.
 *
 * @readonly
 */
interface ChannelInterface
{
    /**
     * The name of the channel.
     *
     * @var non-empty-string
     */
    public string $name {
        get;
    }
}
