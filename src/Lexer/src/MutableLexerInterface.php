<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer;

/**
 * Interface MutableLexerInterface
 */
interface MutableLexerInterface
{
    /**
     * @param string $token
     * @param string $pattern
     * @return MutableLexerInterface|$this
     */
    public function append(string $token, string $pattern): self;

    /**
     * @param array|string[] $tokens
     * @return MutableLexerInterface|$this
     */
    public function appendMany(array $tokens): self;

    /**
     * @param string $token
     * @param string $pattern
     * @return MutableLexerInterface|$this
     */
    public function prepend(string $token, string $pattern): self;

    /**
     * @param array|string[] $tokens
     * @param bool $reverseOrder
     * @return MutableLexerInterface|$this
     */
    public function prependMany(array $tokens, bool $reverseOrder = true): self;

    /**
     * @param string ...$names
     * @return MutableLexerInterface|$this
     */
    public function skip(string ...$names): self;
}
