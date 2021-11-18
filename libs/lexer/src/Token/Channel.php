<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\ChannelInterface;

enum Channel: int implements ChannelInterface
{
    /**
     * General token's type matches any non-special token.
     *
     * @var int
     */
    case GENERAL = 0x00;

    /**
     * Hidden tokens channel name.
     *
     * @var int
     */
    case HIDDEN = 0x01;

    /**
     * This token's type matches any unrecognized token.
     *
     * @var int
     */
    case UNKNOWN = 0x02;

    /**
     * This token's type corresponds to a terminal token and can only be
     * singular in the entire token stream.
     *
     * @var int
     */
    case END_OF_INPUT = 0x03;

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }
}
