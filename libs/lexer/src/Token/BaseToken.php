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

abstract class BaseToken implements TokenInterface, \JsonSerializable
{
    /**
     * @var int<0, max>|null
     */
    private ?int $bytes = null;

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'value' => $this->getValue(),
            'offset' => $this->getOffset(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getBytes(): int
    {
        return $this->bytes ?? $this->bytes = \strlen($this->getValue());
    }

    public function __toString(): string
    {
        if (\class_exists(Renderer::class)) {
            return (new Renderer())->render($this);
        }

        return $this->getName();
    }
}
