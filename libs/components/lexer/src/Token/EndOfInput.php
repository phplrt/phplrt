<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;

class EndOfInput extends Token
{
    private const int TOKEN_ID = -1;
    private const string TOKEN_VALUE = '';
    private const ChannelInterface TOKEN_CHANNEL = Channel::EndOfInput;

    /**
     * @param int<0, max> $offset
     */
    public function __construct(int $offset)
    {
        parent::__construct(
            id: self::TOKEN_ID,
            offset: $offset,
            value: self::TOKEN_VALUE,
            channel: self::TOKEN_CHANNEL,
        );
    }

    public function __toString(): string
    {
        return '';
    }
}
