<?php

declare(strict_types=1);

namespace Phplrt\Position\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

/**
 * A source whose data is no longer available in full.
 */
class NotRewindableException extends \LogicException implements SourceExceptionInterface
{
    final public const int CODE_SOURCE_CONSUMED = 0x01;

    /**
     * @param int<1, max> $offset
     */
    public static function becauseSourceIsConsumed(int $offset, ?\Throwable $prev = null): self
    {
        $message = 'The source does not support offset (seek/rewind) changes and '
            . 'has already given away its first %d bytes';

        return new self(\sprintf($message, $offset), self::CODE_SOURCE_CONSUMED, $prev);
    }
}
