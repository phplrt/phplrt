<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Loader\GrammarReference;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when the grammar a reference points at cannot be read.
 *
 * The error itself has happened in another grammar, so this one only says
 * where that grammar has been asked for: the two are printed one after
 * another, the way a stack of includes is read.
 */
final class IncludeException extends CompilerRuntimeException
{
    public static function fromThrowable(
        ReadableInterface $source,
        GrammarReference $reference,
        \Throwable $previous,
    ): self {
        $message = \sprintf('An error occurred in "%s"', $reference->target);

        return new self(
            source: $source,
            offset: $reference->offset,
            message: $message,
            end: $reference->offset + $reference->length,
            previous: $previous,
        );
    }
}
