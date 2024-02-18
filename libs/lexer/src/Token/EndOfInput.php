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
     * @var int<0, max>
     */
    private int $offset;

    /**
     * @param int<0, max> $offset
     */
    public function __construct(int $offset = 0)
    {
        $this->offset = $offset;
    }

    public function getName(): string
    {
        return self::END_OF_INPUT;
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
