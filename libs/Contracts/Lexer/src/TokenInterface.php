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
 * The lexical token that returns from LexerInterface
 */
interface TokenInterface
{
    /**
     * Name of the token that marks the end of the incoming data.
     *
     * @var string
     */
    public const END_OF_INPUT = 'T_EOI';

    /**
     * Returns a token name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Token position in bytes
     *
     * @return int
     */
    public function getOffset(): int;

    /**
     * Returns the value of the captured subgroup
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * The token value size in bytes
     *
     * @return int
     */
    public function getBytes(): int;
}
