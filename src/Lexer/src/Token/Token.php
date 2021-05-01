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
use Phplrt\Lexer\Driver\DriverInterface;
use Phplrt\Lexer\Renderer\Renderer;

class Token extends BaseToken
{
    /**
     * @var int
     */
    private int $offset;

    /**
     * @var string
     */
    private string $value;

    /**
     * @var string
     */
    private string $name;

    /**
     * @param string $name
     * @param string $value
     * @param int $offset
     */
    public function __construct(string $name, string $value, int $offset)
    {
        $this->name   = $name;
        $this->value  = $value;
        $this->offset = $offset;
    }

    /**
     * @return TokenInterface
     */
    public static function empty(): TokenInterface
    {
        return new self(DriverInterface::UNKNOWN_TOKEN_NAME, '', 0);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
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
    public function __toString(): string
    {
        return (new Renderer())->render($this);
    }
}
