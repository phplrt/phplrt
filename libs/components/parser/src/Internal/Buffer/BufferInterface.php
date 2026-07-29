<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Exception\OutOfRangeException;

/**
 * @template-covariant TToken of TokenInterface = TokenInterface
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
interface BufferInterface
{
    /**
     * The token at the current position.
     *
     * @var TToken
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
     * Rewinds the cursor back to a position it has already been at.
     *
     * @throws OutOfRangeException in case of the position is outside the input
     */
    public function seek(int $offset): void;

    /**
     * Moves the cursor to the next token. Once the input has been read to its
     * end the cursor just stays on the terminal token.
     */
    public function next(): void;
}
