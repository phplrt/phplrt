<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

readonly class UserDefinedChannel implements ChannelInterface
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $name,
    ) {}
}
