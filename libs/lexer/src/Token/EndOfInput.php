<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

final class EndOfInput extends BaseToken
{
    /**
     * @var string
     */
    private const EOI_VALUE = "\0";

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

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return self::END_OF_INPUT;
    }

    /**
     * {@inheritDoc}
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(): string
    {
        return self::EOI_VALUE;
    }

    /**
     * {@inheritDoc}
     */
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
