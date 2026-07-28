<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Internal;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Exception\EmptyTokenException;
use Phplrt\Lexer\Exception\PcreErrorException;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\UnknownToken;

/**
 * Reads what a single lexer recognizes on its own.
 *
 * The executor knows nothing about the other lexers: it only stops as soon as
 * a token that breaks the analysis has been read, leaving the decision on what
 * to do next to the {@see Lexer}.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Lexer
 */
final readonly class Tokenizer
{
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
        /**
         * The number of subgroups each token definition has, indexed by the
         * token IDs. A token that is not mentioned captures nothing.
         *
         * @var array<int, int<1, max>>
         */
        private array $subgroups = [],
    ) {}

    /**
     * Appends every token it reads to the given list, stopping as soon as a
     * token that breaks the analysis has been read.
     *
     * Writing into the caller's list (instead of returning an own one) keeps
     * the tokens of the whole reading in a single array, so no merging is
     * needed.
     *
     * @param string $content the source code that has been read out of the
     *        source object
     * @param int<0, max> $offset
     * @param list<Token> $tokens
     *
     * @param-out list<Token> $tokens
     *
     * @return int<0, max> the offset the analysis has stopped at
     */
    public function tokenize(ReadableInterface $source, string $content, int $offset, array &$tokens): int
    {
        \preg_match_all($this->pattern, $content, $matches, 0, $offset);

        if (!isset($matches['MARK'])) {
            return $this->assertCompleted($source, $content, $offset);
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
        $subgroups = $this->subgroups;

        /**
         * A lexer without transitions reads everything it can, so the (much
         * cheaper) boolean check keeps it from paying for the hash lookup on
         * every single token.
         */
        $isBreakable = $breaks !== [];

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
            $value = $foundValues[$index];
            $length = \strlen($value);

            $token->id = $id;           // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            $token->offset = $offset;   // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            $token->value = $value;     // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass

            /**
             * The prototype is nameless and belongs to the default channel, so
             * only the tokens that are not, pay for the writing.
             */
            if (isset($names[$id])) {
                $token->name = $names[$id];         // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            }

            if (isset($channels[$id])) {
                $token->channel = $channels[$id];   // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            }

            if ($length === 0) {
                throw EmptyTokenException::becauseTokenIsEmpty($source, $token);
            }

            /**
             * The subgroups of all the token definitions share their numbers,
             * so only the ones this token has are read.
             */
            if (isset($subgroups[$id])) {
                $captures = [];

                for ($group = 1, $count = $subgroups[$id]; $group <= $count; ++$group) {
                    $captures[] = $matches[$group][$index];
                }

                $token->captures = $captures;   // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            }

            $tokens[] = $token;
            $offset += $length;

            if ($isBreakable && isset($breaks[$id])) {
                /**
                 * The analysis has been stopped on purpose, so the rest of the
                 * source is none of this lexer's business.
                 */
                return $offset;
            }
        }

        return $this->assertCompleted($source, $content, $offset);
    }

    /**
     * The pattern could not be applied any further, so anything left in the
     * source is unreadable for this lexer.
     *
     * @param int<0, max> $offset
     * @return int<0, max>
     * @throws UnrecognizedTokenException
     */
    private function assertCompleted(ReadableInterface $source, string $content, int $offset): int
    {
        if ($offset >= \strlen($content)) {
            return $offset;
        }

        $token = new UnknownToken(
            value: \substr($content, $offset, self::ERROR_FRAGMENT_LENGTH),
            offset: $offset,
        );

        if (\preg_last_error() !== \PREG_NO_ERROR) {
            throw PcreErrorException::becausePcreErrorOccurs($source, $token, \preg_last_error_msg());
        }

        throw UnrecognizedTokenException::becauseInputIsUnrecognized($source, $token);
    }
}
