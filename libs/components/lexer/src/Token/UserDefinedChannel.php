<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\ChannelInterface;

final readonly class UserDefinedChannel implements ChannelInterface
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $value,
    ) {}
}
