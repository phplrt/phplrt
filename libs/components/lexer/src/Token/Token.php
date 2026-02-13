<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

class Token implements TokenInterface
{
    public function __construct(
        /**
         * @readonly
         */
        public int $id = -1,
        /**
         * @readonly
         * @var non-empty-string|null
         */
        public ?string $name = null,
        /**
         * @readonly
         * @var int<0, max>
         */
        public int $offset = 0,
        /**
         * @readonly
         */
        public string $value = '',
        public ?ChannelInterface $channel = null,
    ) {}

    public function __toString(): string
    {
        return $this->value;
    }
}
