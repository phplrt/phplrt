<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Renderer\Renderer;

class Token extends BaseToken
{
    /**
     * @var positive-int|0
     * @psalm-readonly
     */
    public int $offset;

    /**
     * @var string
     * @psalm-readonly
     */
    public string $value;

    /**
     * @var string
     * @psalm-readonly
     */
    public string $name;

    /**
     * @param string $name
     * @param string $value
     * @param positive-int|0 $offset
     */
    public function __construct(string $name, string $value, int $offset)
    {
        $this->name = $name;
        $this->value = $value;
        $this->offset = $offset;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(): string
    {
        return $this->value;
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
    public function __toString(): string
    {
        return (new Renderer())->render($this);
    }
}
