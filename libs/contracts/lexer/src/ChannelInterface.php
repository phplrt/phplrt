<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

interface ChannelInterface
{
    /**
     * Gets a channel name
     */
    public string $name {
        get;
    }
}
