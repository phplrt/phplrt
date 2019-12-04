<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

/**
 * Class EndOfInput
 */
final class EndOfInput extends BaseToken
{
    /**
     * @var string
     */
    private const EOI_VALUE = "\0";

    /**
     * @var int
     */
    private $offset;

    /**
     * EndOfInput constructor.
     *
     * @param int $offset
     */
    public function __construct(int $offset = 0)
    {
        $this->offset = $offset;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::END_OF_INPUT;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return self::EOI_VALUE;
    }

    /**
     * @return int
     */
    public function getBytes(): int
    {
        return 0;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return '\0';
    }
}
