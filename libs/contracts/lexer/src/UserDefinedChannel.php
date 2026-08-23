<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * A channel that is defined outside of the basic set of the token channels.
 */
readonly class UserDefinedChannel implements ChannelInterface
{
    public function __construct(
        /**
         * The name of the channel.
         *
         * @var non-empty-string
         */
        public string $name,
    ) {}
}
