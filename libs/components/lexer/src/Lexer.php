<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;
use Phplrt\Source\SourceFactory;

readonly class Lexer implements LexerInterface
{
    /**
     * @var array<int, ChannelInterface>
     */
    private array $channels;

    private SourceFactoryInterface $sources;

    public function __construct(
        private LexerCreateInfo $config,
        ?SourceFactoryInterface $sources = null,
    ) {
        $this->sources = $sources ?? SourceFactory::default();
        $this->channels = $this->mapTokenIdToChannel($config);
    }

    /**
     * Gets the lexer configuration and initializes the mapping of tokens to channels.
     *
     * @return array<int, ChannelInterface>
     */
    private function mapTokenIdToChannel(LexerCreateInfo $config): array
    {
        $result = [];

        $channels = $this->createChannelInstances($config);

        foreach ($config->channels as $tokenId => $channelName) {
            $result[$tokenId] = $channels[$channelName];
        }

        return $result;
    }

    /**
     * Gets the lexer configuration and initializes channel instances.
     *
     * @return array<non-empty-string, ChannelInterface>
     */
    private function createChannelInstances(LexerCreateInfo $config): array
    {
        $result = [];

        foreach ($config->channels as $channelName) {
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

    final public function lex(mixed $source, int $offset = 0): iterable
    {
        $readable = $this->sources->create($source);

        return $this->execute($readable, $offset);
    }

    /**
     * @return iterable<array-key, TokenInterface>
     */
    private function execute(ReadableInterface $source, int $offset): iterable
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset cannot be negative');
        }

        $content = $source->content;

        \preg_match_all($this->config->pattern, $content, $matches, 0, $offset);

        if (!isset($matches['MARK'])) {
            return [new EndOfInputToken($source, $offset)];
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
        $names = $this->config->names;
        $channels = $this->channels;

        $prototype = new Token(
            id: -1,
            name: null,
            channel: Channel::DEFAULT,
            source: $source,
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

        $result[] = new EndOfInputToken($source, $offset);

        return $result;
    }
}
