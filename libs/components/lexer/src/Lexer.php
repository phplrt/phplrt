<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\UserDefinedChannel;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Exception\LexerSourceException;
use Phplrt\Lexer\Internal\Tokenizer;
use Phplrt\Lexer\Internal\TokenPrototypeLoader;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\TokenEmbedding;

readonly class Lexer implements LexerInterface
{
    /**
     * The channels a lexer says nothing about are not reported.
     *
     * @var non-empty-list<ChannelInterface>
     */
    public const array DEFAULT_SKIP_CHANNELS = [
        Channel::Hidden,
    ];

    /**
     * Reads everything this lexer recognizes on its own.
     */
    private Tokenizer $tokenizer;

    /**
     * A map of token ID and what the token does to the reading.
     *
     * @var array<int, LexerInterface|null>
     */
    private array $transitions;

    /**
     * The channels that are not reported, keyed by name, so that a token read
     * by a lexer of another kind is a lookup rather than a search through a
     * list.
     *
     * @var array<non-empty-string, true>
     */
    private array $excluded;

    /**
     * The pattern, channels and names are fully consumed by the executor, so
     * the lexer itself only keeps what it needs to reach the other lexers.
     *
     * @param non-empty-string $pattern
     * @param array<int, non-empty-string> $channels
     * @param array<int, non-empty-string> $names
     * @param array<int, LexerInterface|null> $transitions
     * @param array<int, int<1, max>> $subgroups
     * @param iterable<mixed, ChannelInterface> $skip
     */
    public function __construct(
        /**
         * Generated a PCRE2-compatible regex pattern
         *
         * ```php
         * pattern: '/\\G(?|(?:(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)")(*MARK:0))|(?:(?:.+?)(*MARK:1)))/Ssum',
         * ```
         */
        string $pattern,
        /**
         * A map of token ID and its channels.
         *
         * The list contains the token ID in the array's key and the
         * channel name in the array's value. All reserved channels will be
         * converted to built-in ({@see Channel}), all others to the
         * {@see UserDefinedChannel} instance
         *
         * ```php
         * [
         *     0 => 'Hidden',
         *     1 => 'Unknown',
         * ]
         * ```
         */
        array $channels = [],
        /**
         * A map of token ID and its original names.
         */
        array $names = [],
        /**
         * A map of token ID and what the token does to the reading.
         *
         * The token is always read by this lexer itself, so whatever happens
         * next happens AFTER it has been read.
         *
         * - A {@see LexerInterface} value hands the reading over to that
         *   lexer. Everything it reads is carried by the token itself, so it
         *   never reaches this stream. Any implementation is allowed here,
         *   which makes it possible to embed a foreign lexer into the grammar:
         *   such a lexer reads a fragment on its own and returns the control
         *   back as soon as it stops, so its terminal token is not carried
         *   over.
         * - A {@see null} value ends the reading, which is how a lexer called
         *   by another one gives the control back.
         *
         * Note: A lexer knows nothing about the lexers of its neighbours, and
         *       the tokens it reads are carried by the token that called it,
         *       so neither the identifiers nor the names of two lexers can
         *       ever be confused with each other.
         *
         * ```php
         * [
         *     // token #0 is read along with the string it opens
         *     0 => new Lexer(pattern: '...', transitions: [42 => null]),
         *     // token #1 is read along with the PHP code it opens
         *     1 => new PhpTokenLexer(),
         *     // token #2 is the last one this lexer reads
         *     2 => null,
         * ]
         * ```
         */
        array $transitions = [],
        /**
         * A map of token ID and the number of subgroups its definition has.
         *
         * The subgroups of all the token definitions share their numbers, so
         * this is the only thing telling which of them belong to which token.
         * A token mentioned here is read with its {@see Token::$captures}
         * filled, while the one that is not, captures nothing.
         *
         * ```php
         * [
         *     0 => 2, // the definition of token #0 has two subgroups
         *     3 => 1,
         * ]
         * ```
         */
        array $subgroups = [],
        /**
         * The channels this lexer reads but does not report.
         *
         * A token on such a channel is recognized the same way, so the reading
         * goes on exactly as it would otherwise, and it is only left out of the
         * stream: nothing downstream ever sees it.
         */
        iterable $skip = self::DEFAULT_SKIP_CHANNELS,
    ) {
        $this->excluded = self::formatLexerSkippedTokens($skip);
        $this->transitions = $transitions;

        $this->tokenizer = new Tokenizer(
            pattern: $pattern,
            prototypes: TokenPrototypeLoader::load($channels, $names),
            // A transition that ends the reading carries no lexer, so we can't
            // just hand the transitions over and "isset()" them there. The
            // tokenizer needs the plain set of IDs it has to stop after.
            breaks: \array_fill_keys(\array_keys($transitions), true),
            skip: self::formatTokenizerSkippedTokens($channels, $transitions, $this->excluded),
            subgroups: $subgroups,
        );
    }

    /**
     * Returns a list of excluded token channels at the tokenizer's level.
     *
     * @param iterable<mixed, ChannelInterface> $excluded
     * @return array<non-empty-string, true>
     */
    private static function formatLexerSkippedTokens(iterable $excluded): array
    {
        $result = [];

        foreach ($excluded as $channel) {
            $result[$channel->name] = true;
        }

        return $result;
    }

    /**
     * Returns a list of excluded token channels at the lexer's level.
     *
     * @param array<int, non-empty-string> $channels
     * @param array<int, LexerInterface|null> $transitions
     * @param array<non-empty-string, true> $excluded
     * @return array<int, true>
     */
    private static function formatTokenizerSkippedTokens(
        array $channels,
        array $transitions,
        array $excluded,
    ): array {
        if ($excluded === []) {
            return [];
        }

        $result = [];

        foreach ($channels as $id => $channel) {
            /**
             * If exclude a token with a transition, the "state switching" will
             * break. Therefore, it should be left in when passed to the
             * {@see Tokenizer} and only excluded after it's created in the
             * lexer {@see self::execute()} method.
             */
            if (isset($excluded[$channel]) && !\array_key_exists($id, $transitions)) {
                $result[$id] = true;
            }
        }

        return $result;
    }

    final public function lex(ReadableInterface $source, int $offset = 0): iterable
    {
        // Invariant against the callers not covered by static analysis.
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset cannot be negative');
        }

        try {
            $content = $source->content;
        } catch (SourceExceptionInterface $e) {
            throw LexerSourceException::becauseSourceIsNotReadable($e);
        }

        return $this->execute($source, $content, $offset);
    }

    /**
     * Reads the tokens of the source code that has already been read out of
     * the source object, which is carried along only so that an error may
     * refer to it.
     *
     * @param int<0, max> $offset
     * @return list<Token>
     * @throws LexerExceptionInterface
     * @throws RuntimeExceptionInterface
     */
    private function execute(ReadableInterface $source, string $content, int $offset): array
    {
        $length = \strlen($content);

        /** @var list<Token> $result */
        $result = [];

        while ($offset < $length) {
            $indexBeforeExecution = \array_key_last($result);

            $offset = $this->tokenizer->tokenize($source, $content, $offset, $result);

            $indexAfterExecution = \array_key_last($result);

            /**
             * The reading is handed over by the token it has stopped at, so a
             * pass that has added no token of its own has read everything it
             * could: whatever is at the end of the list has been read, and
             * entered, before.
             */
            if ($indexAfterExecution === null || $indexAfterExecution === $indexBeforeExecution) {
                break;
            }

            $lexer = $this->transitions[$result[$indexAfterExecution]->id] ?? null;

            /**
             * The executor only stops on purpose at a token that hands the
             * reading over, so anything else means this lexer has read
             * everything it could.
             */
            if ($lexer === null) {
                break;
            }

            $embedding = $this->enter($lexer, $source, $content, $offset, $result[$indexAfterExecution]);

            $result[$indexAfterExecution] = $embedding;
            $offset = $embedding->offset + $embedding->size;
        }

        $result[] = new EndOfInputToken($offset);

        return $result;
    }

    /**
     * Hands the reading over to the given lexer and carries everything it has
     * read by the token that called it.
     *
     * @param int<0, max> $offset
     * @throws LexerExceptionInterface
     * @throws RuntimeExceptionInterface
     */
    private function enter(
        LexerInterface $lexer,
        ReadableInterface $source,
        string $content,
        int $offset,
        Token $token,
    ): TokenEmbedding {
        /**
         * The source code has already been read, so a lexer of the same kind
         * is entered without reading it over again. Any other one is given the
         * source object and decides on its own how to read it.
         */
        $stream = $lexer instanceof self
            ? $lexer->execute($source, $content, $offset)
            : $lexer->lex($source, $offset);

        $excluded = $this->excluded;

        $children = [];
        $end = $offset;

        foreach ($stream as $child) {
            /**
             * The terminal token only marks the end of the embedded lexer's
             * own fragment, so it is not carried over.
             */
            if ($child->channel === Channel::EndOfInput) {
                $end = $child->offset;

                break;
            }

            // How far the reading has gone is a question about the source
            // rather than about the stream, so a token that is not carried
            // over is still a token that has been read
            $end = $child->offset + $child->size;

            // A lexer of another kind knows nothing about the channels this one
            // does not report, and a lexer of the same kind has already left
            // them out, so this is where a foreign stream is brought in line
            // with the rest.
            if (isset($excluded[$child->channel->name])) {
                continue;
            }

            $children[] = $child;
        }

        // The embedding starts where the token that entered the lexer does,
        // rather than where the lexer itself has started reading
        return TokenEmbedding::createFromToken($token, $children, \max(0, $end - $token->offset));
    }
}
