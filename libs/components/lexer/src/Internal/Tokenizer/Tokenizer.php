<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Internal\Tokenizer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Exception\EmptyTokenException;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;
use Phplrt\Lexer\Token\Token;

/**
 * Reads a single lexer state.
 *
 * The executor knows nothing about the lexer states: it only stops as soon as
 * a token that breaks the analysis has been read, leaving the decision on what
 * to do next to the {@see Lexer}.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Lexer
 */
final readonly class Tokenizer implements TokenizerInterface
{
    /**
     * An identifier of the pseudo-token describing a source fragment
     * that could not be read.
     */
    private const int UNKNOWN_TOKEN_ID = -1;

    /**
     * Max length (in bytes) of the source fragment mentioned in error messages.
     *
     * @var int<1, max>
     */
    private const int ERROR_FRAGMENT_LENGTH = 64;

    public function __construct(
        /**
         * @var non-empty-string
         */
        private string $pattern,
        /**
         * @var array<int, ChannelInterface>
         */
        private array $channels,
        /**
         * @var array<int, non-empty-string>
         */
        private array $names,
        /**
         * A set of token IDs the analysis must stop after.
         *
         * @var array<int, true>
         */
        private array $breaks,
    ) {}

    /**
     * Appends every token it reads to the given list, stopping as soon as a
     * token that breaks the analysis has been read.
     *
     * Writing into the caller's list (instead of returning an own one) keeps
     * the tokens of all states in a single array, so no merging is needed.
     *
     * @param int<0, max> $offset
     * @param list<TokenInterface> $tokens
     *
     * @param-out list<TokenInterface> $tokens
     *
     * @return int<0, max> the offset the analysis has stopped at
     */
    public function tokenize(string $source, int $offset, array &$tokens): int
    {
        \preg_match_all($this->pattern, $source, $matches, 0, $offset);

        if (!isset($matches['MARK'])) {
            return $this->assertCompleted($source, $offset);
        }

        /**
         * PHP stack optimization:
         *
         * Dereference found variables speeds up access to the
         * "hot" variables memory addresses.
         */
        $foundValues = $matches[0];
        $foundNames = $matches['MARK'];

        /**
         * PHP stack optimization:
         *
         * Import "hot" variables from object properties, which will
         * reduce the number of hops to access the memory address.
         */
        $names = $this->names;
        $channels = $this->channels;
        $breaks = $this->breaks;

        /**
         * A state without transitions cannot be left, so the (much cheaper)
         * boolean check keeps such a state from paying for the hash lookup
         * on every single token.
         */
        $breakable = $breaks !== [];

        $prototype = new Token(
            id: -1,
            name: null,
            channel: Channel::DEFAULT,
            value: '',
            offset: $offset,
        );

        foreach ($foundNames as $index => $alias) {
            /**
             * Clone optimization: speeds up the creation of a new object:
             * faster than instantiation.
             */
            $token = clone $prototype;

            $id = (int) $alias;
            $name = null;
            $value = $foundValues[$index];
            $length = \strlen($value);

            if (isset($names[$id])) {
                $name = $names[$id];
            }

            $token->id = $id;           // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            $token->name = $name;       // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            $token->offset = $offset;   // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            $token->value = $value;     // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass

            if (isset($channels[$id])) {
                $token->channel = $channels[$id];   // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            }

            if ($length === 0) {
                throw EmptyTokenException::becauseTokenIsEmpty($token);
            }

            $tokens[] = $token;
            $offset += $length;

            if ($breakable && isset($breaks[$id])) {
                /**
                 * The analysis has been stopped on purpose, so the rest of the
                 * source is none of this state's business.
                 */
                return $offset;
            }
        }

        return $this->assertCompleted($source, $offset);
    }

    /**
     * The pattern could not be applied any further, so anything left in the
     * source is unreadable for this state.
     *
     * @param int<0, max> $offset
     * @return int<0, max>
     * @throws UnrecognizedTokenException
     */
    private function assertCompleted(string $source, int $offset): int
    {
        if ($offset >= \strlen($source)) {
            return $offset;
        }

        $token = new Token(
            id: self::UNKNOWN_TOKEN_ID,
            name: null,
            channel: Channel::Unknown,
            value: \substr($source, $offset, self::ERROR_FRAGMENT_LENGTH),
            offset: $offset,
        );

        if (\preg_last_error() !== \PREG_NO_ERROR) {
            throw UnrecognizedTokenException::becausePcreErrorOccurs($token, \preg_last_error_msg());
        }

        throw UnrecognizedTokenException::becauseInputIsUnrecognized($token);
    }
}
