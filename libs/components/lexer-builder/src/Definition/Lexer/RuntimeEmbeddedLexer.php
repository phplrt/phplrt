<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Definition\Lexer;

use Phplrt\Contracts\Lexer\LexerInterface;

/**
 * Reads a fragment of the source using the given lexer.
 *
 * For example,
 * ```php
 * $builder->addEmbeddedLexer('php', new PhpTokenLexer());
 * ```
 */
final readonly class RuntimeEmbeddedLexer implements EmbeddedLexerInterface
{
    public function __construct(
        public LexerInterface $lexer,
    ) {}

    public function __toString(): string
    {
        return $this->lexer::class;
    }
}
