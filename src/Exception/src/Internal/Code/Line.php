<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception\Internal\Code;

/**
 * @internal Line is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Exception
 */
class Line implements \Stringable
{
    /**
     * @var positive-int
     */
    private int $number;

    /**
     * @var string
     */
    private string $code;

    /**
     * @param positive-int $number
     * @param string $code
     */
    public function __construct(int $number, string $code)
    {
        $this->number = $number;
        $this->code = $code;
    }

    /**
     * @return positive-int
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getLineSize(): int
    {
        return \strlen((string)$this->number);
    }

    /**
     * @return positive-int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->code;
    }
}
