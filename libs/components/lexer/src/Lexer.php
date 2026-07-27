<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Internal\ChannelLoader;
use Phplrt\Lexer\Internal\Tokenizer;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\TokenEmbedding;

readonly class Lexer implements LexerInterface
{
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
     * The pattern, channels and names are fully consumed by the executor, so
     * the lexer itself only keeps what it needs to reach the other lexers.
     *
     * @param non-empty-string $pattern
     * @param array<int, non-empty-string> $channels
     * @param array<int, non-empty-string> $names
     * @param array<int, LexerInterface|null> $transitions
     */
    public function __construct(
        /**
         * Generated a PCRE2-compatible regex pattern
         *
         * For example,
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
         * {@see CustomChannel} instance
         *
         * For example,
         * ```php
         * [
         *     0 => 'hidden',
         *     1 => 'unknown',
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
         * For example,
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
    ) {
        $this->tokenizer = new Tokenizer(
            pattern: $pattern,
            channels: ChannelLoader::load($channels),
            names: $names,
            breaks: \array_fill_keys(\array_keys($transitions), true),
        );

        $this->transitions = $transitions;
    }

    final public function lex(string $source, int $offset = 0): iterable
    {
        // Invariant against the callers not covered by static analysis.
        if ($offset < 0) { // @phpstan-ignore smaller.alwaysFalse
            throw new \InvalidArgumentException('Offset cannot be negative');
        }

        $length = \strlen($source);

        /** @var list<TokenInterface> $result */
        $result = [];

        while ($offset < $length) {
            $offset = $this->tokenizer->tokenize($source, $offset, $result);

            $index = \array_key_last($result);

            if ($index === null) {
                break;
            }

            $lexer = $this->transitions[$result[$index]->id] ?? null;

            /**
             * The executor only stops on purpose at a token that hands the
             * reading over, so anything else means this lexer has read
             * everything it could.
             */
            if ($lexer === null) {
                break;
            }

            $embedding = $this->enter($lexer, $source, $offset, $result[$index]);

            $result[$index] = $embedding;
            $offset = $embedding->end;
        }

        $result[] = new EndOfInputToken($offset);

        return $result;
    }

    /**
     * Hands the reading over to the given lexer and carries everything it has
     * read by the token that called it.
     *
     * @param int<0, max> $offset
     */
    private function enter(
        LexerInterface $lexer,
        string $source,
        int $offset,
        TokenInterface $token,
    ): TokenEmbedding {
        $children = [];
        $end = $offset;

        foreach ($lexer->lex($source, $offset) as $child) {
            /**
             * The terminal token only marks the end of the embedded lexer's
             * own fragment, so it is not carried over.
             */
            if ($child->channel === Channel::EndOfInput) {
                $end = $child->offset;

                break;
            }

            $children[] = $child;
            $end = $child->end;
        }

        return TokenEmbedding::createFromToken($token, $children, $end);
    }
}
