<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Exception\UndefinedStateException;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;
use Phplrt\Lexer\Internal\ChannelLoader;
use Phplrt\Lexer\Internal\State;
use Phplrt\Lexer\Internal\Tokenizer\DelegatingTokenizer;
use Phplrt\Lexer\Internal\Tokenizer\Tokenizer;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;

readonly class Lexer implements LexerInterface
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

    /**
     * The state described by this lexer.
     */
    private State $initial;

    /**
     * The states reachable from {@see Lexer::$initial}.
     *
     * @var array<non-empty-string, State>
     */
    private array $states;

    /**
     * The pattern, channels and names are fully consumed by the executor, so
     * the lexer itself only keeps what it needs to coordinate the states.
     *
     * @param non-empty-string $pattern
     * @param array<int, non-empty-string> $channels
     * @param array<int, non-empty-string> $names
     * @param array<int, non-empty-string|null> $transitions
     * @param array<non-empty-string, LexerInterface> $states
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
         * A map of token ID and the state transition it triggers.
         *
         * The transition is applied AFTER the token itself has been emitted,
         * so the token always belongs to the state it was matched in.
         *
         * - A {@see string} value switches the lexer to the named state
         *   ({@see Lexer::$states} MUST contain such a state).
         * - A {@see null} value returns the lexer to the state it came from.
         *
         * For example,
         * ```php
         * [
         *     0 => 'string', // token #0 enters the "string" state
         *     1 => null,     // token #1 leaves the current state
         * ]
         * ```
         */
        array $transitions = [],
        /**
         * Name of the state and the lexer reading it.
         *
         * The lexer itself always describes the initial state, while this
         * list contains all the additional ones reachable using
         * {@see Lexer::$transitions}.
         *
         * Any {@see LexerInterface} implementation is allowed here, which makes
         * it possible to embed a foreign lexer into the grammar. Such a lexer
         * reads a fragment on its own and returns the control back as soon as
         * it stops, so its terminal token is not included into the result.
         *
         * Note: The state namespace is flat, so the nested {@see Lexer::$states}
         *       of these lexers are ignored.
         *
         * Note: Token identifiers are not namespaced either. Every state writes
         *       into the same stream, so two embedded lexers may well use the
         *       same identifier for different tokens. Nothing can prevent it
         *       here, because a foreign lexer never says which identifiers it
         *       is going to emit.
         *
         *       What tells such tokens apart is the grammar rather than the
         *       identifier: a rule reading an embedded fragment is only
         *       reachable through the token that entered its state.
         *
         * For example,
         * ```php
         * [
         *      'string' => new Lexer(pattern: '...', transitions: [42 => null]),
         *      'php' => new PhpTokenLexer(), // reads up to "?>" on its own
         * ]
         * ```
         */
        array $states = [],
    ) {
        $this->initial = new State(
            tokenizer: new Tokenizer(
                pattern: $pattern,
                channels: ChannelLoader::load($channels),
                names: $names,
                breaks: \array_fill_keys(\array_keys($transitions), true),
            ),
            transitions: $transitions,
        );

        $this->states = $this->bootLexerStates($states);
    }

    /**
     * Gets the lexer configuration and initializes its states.
     *
     * A native lexer already carries a precompiled state, while any other
     * implementation is wrapped into an executor-compatible decorator.
     *
     * @param array<non-empty-string, LexerInterface> $states
     * @return array<non-empty-string, State>
     */
    private function bootLexerStates(array $states): array
    {
        $result = [];

        foreach ($states as $name => $lexer) {
            $result[$name] = $lexer instanceof self
                ? $lexer->initial
                : new State(new DelegatingTokenizer($lexer));
        }

        return $result;
    }

    final public function lex(string $source, int $offset = 0): iterable
    {
        // Invariant against the callers not covered by static analysis.
        if ($offset < 0) { // @phpstan-ignore smaller.alwaysFalse
            throw new \InvalidArgumentException('Offset cannot be negative');
        }

        $length = \strlen($source);

        /**
         * The current state. Each executor stops on its own as soon as it has
         * read everything it must, so the only thing left to do here is to
         * switch between the states.
         */
        $current = $this->initial;

        /** @var list<State> $stack */
        $stack = [];

        /** @var list<TokenInterface> $result */
        $result = [];

        while ($offset < $length) {
            $previous = $offset;
            $offset = $current->tokenizer->tokenize($source, $offset, $result);

            // Nothing could be read at this offset
            if ($offset === $previous) {
                break;
            }

            $index = \array_key_last($result);

            // The executor has advanced, so at least one token has been read
            \assert($index !== null);

            $next = $current->transitions[$result[$index]->id] ?? null;

            if ($next === null) {
                $current = \array_pop($stack) ?? $this->initial;

                continue;
            }

            $stack[] = $current;
            $current = $this->states[$next]
                ?? throw UndefinedStateException::becauseStateIsNotDefined(
                    state: $next,
                    available: \array_keys($this->states),
                );
        }

        if ($offset < $length) {
            throw $this->createReadingException($source, $offset);
        }

        $result[] = new EndOfInputToken($offset);

        return $result;
    }

    /**
     * @param int<0, max> $offset
     */
    private function createReadingException(string $source, int $offset): UnrecognizedTokenException
    {
        return UnrecognizedTokenException::becauseInputIsUnrecognized(new Token(
            id: self::UNKNOWN_TOKEN_ID,
            name: null,
            channel: Channel::Unknown,
            value: \substr($source, $offset, self::ERROR_FRAGMENT_LENGTH),
            offset: $offset,
        ));
    }
}
