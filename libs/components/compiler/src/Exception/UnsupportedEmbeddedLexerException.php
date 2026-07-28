<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Lexer\Builder\Definition\Lexer\EmbeddedLexerInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\PhpCodeEmbeddedLexer;

/**
 * A fragment of the source is read by a lexer that cannot be written down.
 */
final class UnsupportedEmbeddedLexerException extends GeneratorException
{
    /**
     * @param non-empty-string $name
     */
    public static function becauseEmbeddedLexerCannotBeGenerated(
        string $name,
        EmbeddedLexerInterface $lexer,
        ?\Throwable $previous = null,
    ): self {
        $message = \vsprintf('The fragment "%s" is read by %s, while only a %s can be generated', [
            $name,
            $lexer::class,
            PhpCodeEmbeddedLexer::class,
        ]);

        return new self($message, previous: $previous);
    }
}
