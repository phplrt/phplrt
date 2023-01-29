<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer;

interface MutableLexerInterface
{
    /**
     * @param non-empty-string $token
     * @param non-empty-string $pattern
     * @return MutableLexerInterface|$this
     */
    public function append(string $token, string $pattern): self;

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @return MutableLexerInterface|$this
     */
    public function appendMany(array $tokens): self;

    /**
     * @param non-empty-string $token
     * @param non-empty-string $pattern
     * @return MutableLexerInterface|$this
     */
    public function prepend(string $token, string $pattern): self;

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @param bool $reverseOrder
     * @return MutableLexerInterface|$this
     */
    public function prependMany(array $tokens, bool $reverseOrder = true): self;

    /**
     * @param non-empty-string ...$tokens
     * @return MutableLexerInterface|$this
     */
    public function skip(string ...$tokens): self;

    /**
     * @param non-empty-string ...$tokens
     * @return MutableLexerInterface|$this
     */
    public function remove(string ...$tokens): self;
}
