<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class EndOfInputToken extends Token
{
    /**
     * An "end of input" token identifier
     */
    public const int TOKEN_ID = -1;

    /**
     * Note: This "@var" docblock is required because the php-cs-fixer
     *       contains a bug that can remove the dependency as unused.
     *
     * @var ChannelInterface
     */
    public const ChannelInterface TOKEN_CHANNEL = Channel::EndOfInput;

    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        ReadableInterface $source,
        int $offset,
    ) {
        parent::__construct(
            id: self::TOKEN_ID,
            name: self::TOKEN_CHANNEL->value,
            channel: self::TOKEN_CHANNEL,
            source: $source,
            value: '',
            offset: $offset,
        );
    }
}
