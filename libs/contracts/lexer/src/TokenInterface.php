<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * A single lexical token, which is the smallest meaningful unit a source
 * consists of.
 *
 * The offset of a token MUST be counted in bytes from the beginning of the
 * source, starting at zero, and its size MUST be that of the source fragment
 * the token has been read from, so that the position right after the token is
 * the sum of the two.
 *
 * An implementation MUST be immutable.
 *
 * All properties described below SHOULD BE considered actual interface
 * requirements. Their absence in the code is due to support requirements
 * for PHP versions prior to 8.4.
 *
 * @property-read int $id The identifier of the token type, by which the kinds
 *         of the tokens are told apart from one another.
 *
 *         A significant token SHOULD be identified by a number greater than or
 *         equal to zero, while a system one, like the end of input or an error,
 *         SHOULD be identified by a negative number.
 * @property-read non-empty-string|null $name The human-readable name of the
 *         token type, or {@see null} in case the token type has no name of
 *         its own.
 * @property-read ChannelInterface $channel The channel the token belongs to.
 * @property-read int<0, max> $offset The offset in bytes from the beginning of
 *         the source the token starts at.
 * @property-read int<0, max> $size The size of the source fragment the token
 *         has been read from, in bytes.
 *
 *         An ordinary token is as large as its own value, while a token reading
 *         a fragment using a lexer of its own is as large as that lexer has
 *         read, no matter how short its own value is.
 * @property-read string $value The exact source fragment matched by the lexer.
 *
 *         The value MUST be the original fragment, without any normalization or
 *         transformation, which SHOULD be performed during the syntax analysis.
 *
 * @readonly
 */
interface TokenInterface extends \Stringable
{
    /**
     * The minimal offset a token is allowed to have.
     *
     * @var int<0, max>
     */
    public const MIN_OFFSET = 0;
}
