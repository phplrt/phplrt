<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The lexical token that returns from {@see LexerInterface}
 */
interface TokenInterface extends \Stringable
{
    /**
     * Gets a token name
     *
     * @var non-empty-string
     */
    public string $name {
        get;
    }

    /**
     * Gets a channel of the token
     */
    public ?ChannelInterface $channel {
        get;
    }

    /**
     * Gets token position in bytes
     *
     * @var int<0, max>
     */
    public int $offset {
        get;
    }

    /**
     * Gets the value of the captured subgroup
     */
    public string $value {
        get;
    }

    /**
     * Gets the token value size in bytes
     *
     * @var int<0, max>
     */
    public int $bytes {
        get;
    }
}
