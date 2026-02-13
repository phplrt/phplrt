<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\ChannelInterface;

final readonly class CustomChannel implements ChannelInterface
{
    public string $value;

    /**
     * @param non-empty-string $name
     */
    public function __construct(string $name)
    {
        $this->value = $name;
    }
}
