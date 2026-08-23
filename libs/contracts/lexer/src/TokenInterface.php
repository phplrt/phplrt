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
 * @readonly
 */
interface TokenInterface extends \Stringable
{
    /**
     * The minimal offset a token is allowed to have.
     *
     * @var int<0, max>
     */
    public const int MIN_OFFSET = 0;

    /**
     * The identifier of the token type, by which the kinds of the tokens are
     * told apart from one another.
     *
     * A significant token SHOULD be identified by a number greater than or
     * equal to zero, while a system one, like the end of input or an error,
     * SHOULD be identified by a negative number.
     */
    public int $id {
        get;
    }

    /**
     * The human-readable name of the token type, or {@see null} in case the
     * token type has no name of its own.
     *
     * @var non-empty-string|null
     */
    public ?string $name {
        get;
    }

    /**
     * The channel the token belongs to.
     */
    public ChannelInterface $channel {
        get;
    }

    /**
     * The offset in bytes from the beginning of the source the token
     * starts at.
     *
     * @var int<0, max>
     */
    public int $offset {
        get;
    }

    /**
     * The size of the source fragment the token has been read from, in bytes.
     *
     * An ordinary token is as large as its own value, while a token reading a
     * fragment using a lexer of its own is as large as that lexer has read, no
     * matter how short its own value is.
     *
     * @var int<0, max>
     */
    public int $size {
        get;
    }

    /**
     * The exact source fragment matched by the lexer.
     *
     * The value MUST be the original fragment, without any normalization or
     * transformation, which SHOULD be performed during the syntax analysis.
     */
    public string $value {
        get;
    }
}
