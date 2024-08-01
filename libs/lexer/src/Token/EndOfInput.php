<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

final class EndOfInput extends BaseToken
{
    /**
     * @var non-empty-string
     */
    private const EOI_VALUE = "\0";

    /**
     * Name of the token that marks the end of the incoming data.
     *
     * @var non-empty-string
     */
    public const DEFAULT_TOKEN_NAME = TokenInterface::END_OF_INPUT;

    /**
     * @param int<0, max> $offset
     * @param array-key $name
     */
    public function __construct(
        private readonly int $offset = 0,
        private readonly string $name = self::DEFAULT_TOKEN_NAME,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getValue(): string
    {
        return self::EOI_VALUE;
    }

    public function getBytes(): int
    {
        return 0;
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return '\0';
    }
}
