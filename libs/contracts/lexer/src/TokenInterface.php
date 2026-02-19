<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Represents a single lexical token produced by {@see LexerInterface}.
 *
 * A token is the smallest meaningful unit produced by a lexer.
 * It encapsulates:
 *
 *  - A numeric identifier ({@see $id})
 *  - Channel (for hidden/system tokens, {@see $channel})
 *  - A textual value ({@see $value})
 *  - Positional information ({@see $offset})
 *  - Optional logical name ({@see $name})
 *
 * Note: Implementations MUST guarantee:
 *   - {@see $length} === strlen($value)
 *   - {@see $end} represents the position immediately AFTER the last character
 *   - {@see $offset} is zero-based
 *
 * @readonly An implementation MUST be immutable.
 */
interface TokenInterface extends \Stringable
{
    /**
     * Minimal allowed offset number.
     *
     * @var int<0, max>
     */
    public const int MIN_OFFSET = 0;

    /**
     * Unique token identifier (token type).
     *
     * The parser uses this value to distinguish token kinds.
     *
     * Recommended conventions:
     *  - Significant tokens: `int<0, max>`
     *  - System/internal tokens (e.g. EOF, error, virtual): `int<min, -1>`
     */
    public int $id {
        get;
    }

    /**
     * Logical token name.
     *
     * Optional human-readable identifier of the token type.
     *
     * Is useful for:
     *  - Debugging
     *  - Error reporting
     *  - AST visualization
     *
     * @var non-empty-string|null
     */
    public ?string $name {
        get;
    }

    /**
     * Token channel.
     *
     * Channels allow separating tokens into logical streams.
     *
     * Typical usage:
     *  - Default channel: significant tokens
     *  - Hidden channel: whitespace, comments
     *  - Custom channels: documentation, preprocessor, etc
     */
    public ChannelInterface $channel {
        get;
    }

    /**
     * Absolute byte offset from the beginning of the source.
     *
     * This is zero-based and MUST represent the START of the token.
     *
     * @var int<0, max>
     */
    public int $offset {
        get;
    }

    /**
     * The exact lexeme (captured substring) matched by the lexer.
     *
     * This MUST contain the original source fragment, without normalization
     * or transformation.
     *
     * If semantic normalization is required (e.g. converting "42" to int 42),
     * it SHOULD be performed at parser or AST level.
     */
    public string $value {
        get;
    }
}
