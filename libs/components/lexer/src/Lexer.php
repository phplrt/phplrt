<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;

readonly class Lexer implements LexerInterface
{
    /**
     * @var array<int, ChannelInterface>
     */
    private array $mappedChannels;

    public function __construct(
        /**
         * Generated a PCRE2-compatible regex pattern
         *
         * For example,
         * ```php
         * pattern: '/\\G(?|(?:(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)")(*MARK:0))|(?:(?:.+?)(*MARK:1)))/Ssum',
         * ```
         *
         * @var non-empty-string
         */
        private string $pattern,
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
         *
         * @var array<int, non-empty-string>
         */
        private array $channels = [],
        /**
         * A map of token ID and its original names.
         *
         * @var array<int, non-empty-string>
         */
        private array $names = [],
        /**
         * Name of the state and its implementation.
         *
         * An array contains lexer states.
         *
         * For example,
         * ```php
         * [
         *      'injected_language' => new Lexer(...),
         *      'other_language' => new Lexer(...),
         * ]
         * ```
         *
         * @var array<non-empty-string, LexerInterface>
         */
        private array $states = [],
    ) {
        $this->mappedChannels = $this->mapTokenIdToChannel($this->channels);
    }

    /**
     * Gets the lexer configuration and initializes the mapping of tokens to channels.
     *
     * @return array<int, ChannelInterface>
     */
    private function mapTokenIdToChannel(array $channels): array
    {
        $result = [];
        $instances = $this->createChannelInstances($channels);

        foreach ($channels as $tokenId => $channelName) {
            $result[$tokenId] = $instances[$channelName];
        }

        return $result;
    }

    /**
     * Gets the lexer configuration and initializes channel instances.
     *
     * @param array<int, non-empty-string> $channels
     * @return array<non-empty-string, ChannelInterface>
     */
    private function createChannelInstances(array $channels): array
    {
        $result = [];

        foreach ($channels as $channelName) {
            if (isset($result[$channelName])) {
                continue;
            }

            $result[$channelName] = Channel::tryFrom($channelName)
                ?? $this->createCustomChannel($channelName);
        }

        return $result;
    }

    /**
     * @param non-empty-string $name
     */
    private function createCustomChannel(string $name): ChannelInterface
    {
        return new readonly class ($name) implements ChannelInterface {
            public function __construct(
                /**
                 * @var non-empty-string
                 */
                public string $value,
            ) {}
        };
    }

    final public function lex(string $source, int $offset = 0): iterable
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset cannot be negative');
        }

        \preg_match_all($this->pattern, $source, $matches, 0, $offset);

        if (!isset($matches['MARK'])) {
            return [new EndOfInputToken($offset)];
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
        $channels = $this->mappedChannels;

        $prototype = new Token(
            id: -1,
            name: null,
            channel: Channel::DEFAULT,
            value: '',
            offset: $offset,
        );

        /**
         * PHP memory deoptimization:
         * - Like `$result = \array_fill(0, \count($foundNames) + 1, null);`
         * - Or `$result = new \SplFixedArray(\count($foundNames) + 1);`
         *
         * Allocating memory in advance to the required size
         * DOES NOT significantly affect performance,
         * but it complicates code maintenance.
         */
        $result = [];

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

            $result[] = $token;
            $offset += $length;
        }

        $result[] = new EndOfInputToken($offset);

        return $result;
    }
}
