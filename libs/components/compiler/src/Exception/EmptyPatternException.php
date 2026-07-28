<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Node\Statement\InlinePattern;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a rule declares a token recognizing nothing.
 *
 * Such a token can never advance the lexer position, so a grammar built of it
 * would read the input forever.
 */
final class EmptyPatternException extends CompilerRuntimeException
{
    public static function becausePatternIsEmpty(
        ReadableInterface $source,
        InlinePattern $statement,
        ?\Throwable $previous = null,
    ): self {
        $message = 'A token recognizing nothing cannot be declared';

        return new self(
            source: $source,
            offset: $statement->offset,
            message: $message,
            end: $statement->offset + $statement->length,
            previous: $previous,
        );
    }
}
