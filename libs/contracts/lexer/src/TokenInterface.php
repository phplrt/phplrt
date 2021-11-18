<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The lexical token that returns from {@see LexerInterface}.
 */
interface TokenInterface extends \Stringable
{
    /**
     * Returns a token name.
     *
     * @return non-empty-string|int
     */
    public function getName(): string|int;

    /**
     * Token position in bytes.
     *
     * @return positive-int|0
     */
    public function getOffset(): int;

    /**
     * Returns the value of the captured subgroup.
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Returns channel of the token.
     *
     * @return ChannelInterface
     */
    public function getChannel(): ChannelInterface;
}
