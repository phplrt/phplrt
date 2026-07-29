<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Loader\GrammarReference;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a reference points at a grammar file that cannot be found.
 */
final class GrammarNotFoundException extends CompilerRuntimeException
{
    public static function becauseGrammarIsNotFound(
        ReadableInterface $source,
        GrammarReference $reference,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('%s: failed to open stream: No such file or directory', $reference->target);

        return new self(
            source: $source,
            offset: $reference->offset,
            message: $message,
            length: $reference->length,
            previous: $previous,
        );
    }
}
