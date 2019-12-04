<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Lexer\Driver\DriverInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class Token
 */
class Token extends BaseToken
{
    /**
     * @var int
     */
    private $offset;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string|int
     */
    private $name;

    /**
     * BaseToken constructor.
     *
     * @param string|int $name
     * @param string $value
     * @param int $offset
     */
    public function __construct($name, string $value, int $offset)
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
        return new static(DriverInterface::UNKNOWN_TOKEN_NAME, '', 0);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->containsValidName() ? $this->name : $this->value;
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
        $renderer = new Renderer();

        if ($this->containsValidName()) {
            return $renderer->render($this);
        }

        return $renderer->value($this);
    }

    /**
     * @return bool
     */
    private function containsValidName(): bool
    {
        return \is_string($this->name) && \trim($this->name) !== '';
    }
}
