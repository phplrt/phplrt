<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The lexical token that returns from {@see LexerInterface}
 */
interface TokenInterface extends \Stringable
{
    /**
     * Gets unique token identifier
     */
    public int $id {
        get;
    }

    /**
     * Gets a token name
     *
     * @var non-empty-string|null
     */
    public ?string $name {
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
}
