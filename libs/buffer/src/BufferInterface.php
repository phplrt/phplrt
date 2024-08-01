<?php

declare(strict_types=1);

namespace Phplrt\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * @template-extends \SeekableIterator<int<0, max>, TokenInterface>
 */
interface BufferInterface extends \SeekableIterator
{
    /**
     * Rewind the BufferInterface to the target token element.
     *
     * @link https://php.net/manual/en/seekableiterator.seek.php
     *
     * @see \SeekableIterator::seek()
     *
     * @param int<0, max> $offset
     */
    public function seek($offset): void;

    /**
     * Return the current token.
     *
     * @link https://php.net/manual/en/iterator.current.php
     *
     * @see \Iterator::current()
     */
    public function current(): TokenInterface;

    /**
     * Return the ordinal id of the current token element.
     *
     * @link https://php.net/manual/en/iterator.key.php
     *
     * @see \Iterator::key()
     *
     * @return int<0, max>
     */
    public function key(): int;

    /**
     * Checks if current position is valid and iterator not completed.
     *
     * @link https://php.net/manual/en/iterator.valid.php
     *
     * @see \Iterator::valid()
     */
    public function valid(): bool;

    /**
     * Rewind the BufferInterface to the first token element.
     *
     * @link https://php.net/manual/en/iterator.rewind.php
     *
     * @see \Iterator::rewind()
     */
    public function rewind(): void;

    /**
     * Move forward to next token element.
     *
     * @link https://php.net/manual/en/iterator.next.php
     *
     * @see \Iterator::next()
     */
    public function next(): void;
}
