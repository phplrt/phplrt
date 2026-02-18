<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Lexer\Executor\MarkersExecutor;
use Phplrt\Lexer\Token\CustomChannel;
use Phplrt\Source\SourceFactory;

readonly class Lexer implements LexerInterface
{
    public array $transitions;

    private SourceFactoryInterface $sources;

    private LexerInterface $executor;

    public function __construct(
        public LexerCreateInfo $config,
        ?SourceFactoryInterface $sources = null,
    ) {
        $this->sources = $sources ?? SourceFactory::default();

        $channels = $this->createChannelInstances($config);

        $this->executor = $this->createExecutor($config, $channels);
        $this->transitions = $config->transitions;
    }

    /**
     * @param array<non-empty-string, ChannelInterface> $channels
     */
    private function createExecutor(LexerCreateInfo $config, array $channels): LexerInterface
    {
        return new MarkersExecutor(
            config: $config,
            channels: $this->mapTokenIdToChannel($config, $channels),
        );
    }

    /**
     * Gets the lexer configuration and initializes the mapping of tokens to channels.
     *
     * @param array<non-empty-string, ChannelInterface> $channels
     *
     * @return array<int, ChannelInterface>
     */
    private function mapTokenIdToChannel(LexerCreateInfo $config, array $channels): array
    {
        $result = [];

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
                ?? new CustomChannel($channelName);
        }

        return $result;
    }

    public function lex(mixed $source, int $offset = 0): iterable
    {
        $source = $this->sources->create($source);

        return $this->executor->lex($source, $offset);
    }
}
