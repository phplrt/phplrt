<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * @psalm-type TokenPosition = positive-int | 0
 * @template-extends \SeekableIterator<TokenPosition, TokenInterface>
 */
interface BufferInterface extends \SeekableIterator
{
    /**
     * Rewind the BufferInterface to the target token element.
     *
     * @param TokenPosition $offset
     * @return void
     */
    public function seek(int $offset): void;

    /**
     * Return the current token.
     *
     * @see \Iterator::current()
     * @return TokenInterface
     * @link https://php.net/manual/en/iterator.current.php
     */
    public function current(): TokenInterface;

    /**
     * Return the ordinal id of the current token element.
     *
     * @see \Iterator::key()
     * @return TokenPosition
     * @link https://php.net/manual/en/iterator.key.php
     */
    public function key(): int;

    /**
     * Checks if current position is valid and iterator not completed.
     *
     * @see \Iterator::valid()
     * @return bool
     * @link https://php.net/manual/en/iterator.valid.php
     */
    public function valid(): bool;

    /**
     * Rewind the BufferInterface to the first token element.
     *
     * @see \Iterator::rewind()
     * @return void
     * @link https://php.net/manual/en/iterator.rewind.php
     */
    public function rewind(): void;

    /**
     * Move forward to next token element.
     *
     * @see \Iterator::next()
     * @return void
     * @link https://php.net/manual/en/iterator.next.php
     */
    public function next(): void;
}
