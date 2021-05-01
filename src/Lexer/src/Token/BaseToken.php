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

abstract class BaseToken implements TokenInterface, \JsonSerializable, \Stringable
{
    /**
     * @psalm-var positive-int|0|null
     */
    private ?int $bytes = null;

    /**
     * @return array {
     *      name:   string,
     *      value:  string,
     *      bytes:  positive-int,
     *      offset: positive-int|0,
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'name'   => $this->getName(),
            'value'  => $this->getValue(),
            'bytes'  => $this->getBytes(),
            'offset' => $this->getOffset(),
        ];
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getBytes(): int
    {
        /**
         * @psalm-suppress PropertyTypeCoercion
         * @psalm-suppress LessSpecificReturnStatement
         */
        return $this->bytes ?? $this->bytes = \strlen($this->getValue());
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return (new Renderer())
            ->render($this)
        ;
    }
}
