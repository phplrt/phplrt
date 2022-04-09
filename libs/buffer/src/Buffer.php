<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Buffer;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\EndOfInput;

abstract class Buffer implements BufferInterface
{
    /**
     * @var non-empty-string
     */
    protected const ERROR_STREAM_POSITION_EXCEED =
        'Can not seek to position %d, because the last buffer token has an index %d';

    /**
     * @var non-empty-string
     */
    protected const ERROR_STREAM_POSITION_TO_LOW =
        'Can not seek to a position %d that is less than the initial '
        . 'value (%d) of the first element of the stream';

    /**
     * @var positive-int|0
     */
    protected int $initial = 0;

    /**
     * @var positive-int|0
     */
    protected int $current = 0;

    /**
     * @param TokenInterface $token
     * @return string
     */
    protected function tokenToString(TokenInterface $token): string
    {
        if ($token instanceof \Stringable) {
            return (string)$token;
        }

        return $token->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function key(): int
    {
        return $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->seek($this->initial);
    }

    /**
     * {@inheritDoc}
     */
    public function seek($offset): void
    {
        \assert($offset >= 0);

        $this->current = $offset;
    }

    /**
     * @param array<TokenInterface> $data
     * @return TokenInterface
     * @psalm-suppress PossiblyNullArrayOffset
     */
    protected function currentFrom(array $data): TokenInterface
    {
        if (isset($data[$this->current])) {
            return $data[$this->current];
        }

        return $data[\array_key_last($data)] ?? new EndOfInput();
    }
}
