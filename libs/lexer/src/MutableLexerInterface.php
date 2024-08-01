<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

interface MutableLexerInterface
{
    /**
     * @param non-empty-string $token
     * @param non-empty-string $pattern
     *
     * @return MutableLexerInterface|$this
     */
    public function append(string $token, string $pattern): self;

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     *
     * @return MutableLexerInterface|$this
     */
    public function appendMany(array $tokens): self;

    /**
     * @param non-empty-string $token
     * @param non-empty-string $pattern
     *
     * @return MutableLexerInterface|$this
     */
    public function prepend(string $token, string $pattern): self;

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     *
     * @return MutableLexerInterface|$this
     */
    public function prependMany(array $tokens, bool $reverseOrder = true): self;

    /**
     * @param non-empty-string ...$tokens
     *
     * @return MutableLexerInterface|$this
     */
    public function skip(string ...$tokens): self;

    /**
     * @param non-empty-string ...$tokens
     *
     * @return MutableLexerInterface|$this
     */
    public function remove(string ...$tokens): self;
}
