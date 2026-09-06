<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Stub;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

final class TokenStub implements TokenInterface
{
    public readonly int $size;

    public function __construct(
        public readonly int $offset = self::MIN_OFFSET,
        public readonly string $value = '',
        public readonly int $id = 0,
        public readonly ?string $name = null,
        public readonly ChannelInterface $channel = Channel::Default,
    ) {
        $this->size = \strlen($this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
