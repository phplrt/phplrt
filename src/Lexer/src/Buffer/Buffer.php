<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Buffer;

use Phplrt\Contracts\Lexer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\EndOfInput;

/**
 * Class Buffer
 */
abstract class Buffer implements BufferInterface
{
    /**
     * @var string
     */
    protected const ERROR_STREAM_POSITION_EXCEED =
        'Can not seek to position %d, because the last buffer token has an index %d';

    /**
     * @var string
     */
    protected const ERROR_STREAM_POSITION_TO_LOW =
        'Can not seek to a position %d that is less than the initial value (%d) ' .
        'of the first element of the stream';

    /**
     * @var int
     */
    protected int $initial = 0;

    /**
     * @var int
     */
    protected int $current = 0;

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
    public function seek($position): void
    {
        \assert(\is_int($position));

        $this->current = $position;
    }

    /**
     * @param array|TokenInterface[] $data
     * @return TokenInterface
     */
    protected function currentFrom(array $data): TokenInterface
    {
        if (isset($data[$this->current])) {
            return $data[$this->current];
        }

        if (isset($data[$key = \array_key_last($data)])) {
            return $data[$key];
        }

        return new EndOfInput(0);
    }
}
