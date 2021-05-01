<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

final class EndOfInput extends BaseToken
{
    /**
     * @var string
     */
    private const EOI_VALUE = "\0";

    /**
     * @var positive-int|0
     */
    private int $offset;

    /**
     * @param positive-int|0 $offset
     */
    public function __construct(int $offset = 0)
    {
        $this->offset = \max(0, $offset);
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
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return '\0';
    }
}
