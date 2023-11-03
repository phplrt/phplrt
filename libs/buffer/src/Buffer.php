<?php

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
     * @var int<0, max>
     */
    protected int $initial = 0;

    /**
     * @var int<0, max>
     */
    protected int $current = 0;

    protected function tokenToString(TokenInterface $token): string
    {
        if ($token instanceof \Stringable) {
            return (string)$token;
        }

        return $token->getName();
    }

    public function key(): int
    {
        return $this->current;
    }

    public function rewind(): void
    {
        $this->seek($this->initial);
    }

    public function seek($offset): void
    {
        \assert($offset >= 0);

        $this->current = $offset;
    }

    /**
     * @param array<TokenInterface> $data
     * @psalm-suppress PossiblyNullArrayOffset
     */
    protected function currentFrom(array $data): TokenInterface
    {
        return $data[$this->current]
            ?? $data[\array_key_last($data)]
            ?? new EndOfInput();
    }
}
