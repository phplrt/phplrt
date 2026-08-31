<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Exception\OutOfRangeException;

/**
 * All properties described below SHOULD BE considered actual interface
 * requirements. Their absence in the code is due to support requirements
 * for PHP versions prior to 8.4.
 *
 * @template-covariant TToken of TokenInterface = TokenInterface
 *
 * @property-read TToken $current The token at the current position.
 * @property-read int<0, max> $key The position of the current token.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
interface BufferInterface
{
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
