<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Definition\Lexer;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;
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
    /**
     * The place of the source code this lexer has been written in, in case it
     * has been written at all rather than built by hand.
     */
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
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @return $this
     */
    public function setSource(ReadableInterface $source, int $offset, int $length = 0): self
    {
        $this->context = new SourceReference($source, $offset, $length);

        return $this;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
