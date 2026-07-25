<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * @template-covariant TToken of TokenInterface = TokenInterface
 *
 * @template-extends \SeekableIterator<int<0, max>, TToken>
 */
interface BufferInterface extends \SeekableIterator
{
    /**
     * The token at the current position.
     */
    public TokenInterface $current {
        get;
    }

    /**
     * The position of the current token.
     *
     * @var int<0, max>
     */
    public int $key {
        get;
    }

    /**
     * Rewind the BufferInterface to the target token element.
     *
     * @link https://php.net/manual/en/seekableiterator.seek.php
     *
     * @see \SeekableIterator::seek()
     */
    public function seek(int $offset): void;

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
     * Move forward to the next token element.
     *
     * @link https://php.net/manual/en/iterator.next.php
     *
     * @see \Iterator::next()
     */
    public function next(): void;
}
