<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Definition\Lexer;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Builder\Definition\SourceReference;

/**
 * Reads a fragment of the source using the lexer the given PHP code produces.
 *
 * The code is an expression, so what it evaluates to is the business of the
 * grammar it is written in, as long as it is a {@see LexerInterface}.
 *
 * For example,
 * ```php
 * $builder->addEmbeddedLexer('php', new PhpCodeEmbeddedLexer('new \App\PhpTokenLexer()'));
 * ```
 */
final class PhpCodeEmbeddedLexer implements EmbeddedLexerInterface
{
    public private(set) ?SourceReference $context = null;

    /**
     * @param non-empty-string $code
     */
    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $code,
    ) {}

    /**
     * @param non-empty-string $pathname
     * @param int<0, max> $offset
     * @return $this
     */
    public function setSource(string $pathname, int $offset): self
    {
        $this->context = new SourceReference($pathname, $offset);

        return $this;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
