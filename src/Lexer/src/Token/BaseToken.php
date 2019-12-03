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

/**
 * Class BaseToken
 */
abstract class BaseToken implements TokenInterface, \JsonSerializable
{
    /**
     * @var int|null
     */
    private $bytes;

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'name'   => $this->getName(),
            'value'  => $this->getValue(),
            'offset' => $this->getOffset(),
        ];
    }

    /**
     * @return int
     */
    public function getBytes(): int
    {
        return $this->bytes ?? $this->bytes = \strlen($this->getValue());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (new Renderer())->render($this);
    }
}
