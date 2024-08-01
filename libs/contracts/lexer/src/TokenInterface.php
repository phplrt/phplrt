<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The lexical token that returns from LexerInterface
 */
interface TokenInterface
{
    /**
     * Returns a token name.
     *
     * @return non-empty-string
     */
    public function getName(): string;

    /**
     * Token position in bytes
     *
     * @return int<0, max>
     */
    public function getOffset(): int;

    /**
     * Returns the value of the captured subgroup
     */
    public function getValue(): string;

    /**
     * The token value size in bytes
     *
     * @return int<0, max>
     */
    public function getBytes(): int;
}
