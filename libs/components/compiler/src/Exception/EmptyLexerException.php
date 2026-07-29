<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Node\Declaration\LexerDeclaration;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Occurs when a grammar declares a lexer written of no code.
 *
 * Such a declaration names a state that nothing reads, so a token entering it
 * would hand the reading over to nowhere.
 */
final class EmptyLexerException extends CompilerRuntimeException
{
    public static function becauseLexerIsEmpty(
        ReadableInterface $source,
        LexerDeclaration $declaration,
        ?\Throwable $previous = null,
    ): self {
        $message = \sprintf('The "%s" lexer is written of no code at all', $declaration->name);

        return new self(
            source: $source,
            offset: $declaration->offset,
            message: $message,
            length: $declaration->length,
            previous: $previous,
        );
    }
}
