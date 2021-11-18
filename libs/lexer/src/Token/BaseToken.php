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
use Phplrt\Lexer\Printer\Printer;

abstract class BaseToken implements TokenInterface, \JsonSerializable
{
    /**
     * @return array {
     *  name: non-empty-string|int,
     *  value: string,
     *  offset: positive-int|0,
     * }
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
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return (new Printer())->print($this);
    }
}
