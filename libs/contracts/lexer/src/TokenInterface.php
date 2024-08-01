<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * The lexical token that returns from LexerInterface
 */
interface TokenInterface
{
    /**
     * Name of the token that marks the end of the incoming data.
     *
     * @var non-empty-string
     *
     * @deprecated since 3.6 and will be removed in 4.0.
     *             Please use vendor-specific token names instead,
     *             like {@see \Phplrt\Lexer\Token\EndOfInput::DEFAULT_TOKEN_NAME}.
     */
    public const END_OF_INPUT = 'T_EOI';

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
