<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer;

use JetBrains\PhpStorm\Language;
use Phplrt\Contracts\Lexer\LexerInterface;

interface MutableLexerInterface extends LexerInterface
{
    /**
     * @param string $token
     * @param string $pattern
     * @return MutableLexerInterface|$this
     */
    public function append(
        string $token,
        #[Language("RegExp")]
        string $pattern
    ): self;

    /**
     * @param string $token
     * @param string $pattern
     * @return MutableLexerInterface|$this
     */
    public function prepend(
        string $token,
        #[Language("RegExp")]
        string $pattern
    ): self;

    /**
     * @param string ...$tokens
     * @return MutableLexerInterface|$this
     */
    public function skip(string ...$tokens): self;

    /**
     * @param string ...$tokens
     * @return MutableLexerInterface|$this
     */
    public function remove(string ...$tokens): self;
}
