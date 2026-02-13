<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Executor\MarkersExecutor;
use Phplrt\Lexer\Token\CustomChannel;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Source\SourceFactory;

readonly class Lexer implements LexerInterface
{
    public array $transitions;

    private SourceFactoryInterface $sources;

    private LexerInterface $executor;

    /**
     * @var array<non-empty-string, LexerInterface>
     */
    private array $states;

    public function __construct(
        LexerCreateInfo $config,
        ?SourceFactoryInterface $sources = null,
    ) {
        $this->sources = $sources ?? SourceFactory::default();

        $channels = $this->createChannelInstances($config);

        $this->executor = $this->createExecutor($config, $channels);
        $this->transitions = $config->transitions;
        $this->states = $config->states;
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

    /**
     * @param int<0, max> $offset
     *
     * @return iterable<array-key, TokenInterface>
     * @throws LexerExceptionInterface
     * @throws RuntimeExceptionInterface
     */
    private function executeStateless(ReadableInterface $source, int $offset): iterable
    {
        return $this->executor->lex($source, $offset);
    }

    /**
     * @param int<0, max> $offset
     *
     * @return iterable<array-key, TokenInterface>
     * @throws LexerExceptionInterface
     * @throws RuntimeExceptionInterface
     */
    private function executeStateful(ReadableInterface $source, int $offset): iterable
    {
        $states = $this->states;
        $executor = $this->executor;
        $transitions = $executor->transitions;

        $completed = false;
        $result = [];

        do {
            /** @var TokenInterface $token */
            foreach ($executor->lex($source, $offset) as $token) {
                if ($token->channel === Channel::EndOfInput) {
                    $completed = true;
                    break;
                }

                $result[] = $token;

                if (\array_key_exists($token->id, $transitions)) {
                    $executor = $states[$transitions[$token->id]];
                    $transitions = $executor->transitions;
                }
            }
        } while (!$completed);

        $result[] = $token ?? new EndOfInput(0);

        return $result;
    }

    public function lex(mixed $source, int $offset = 0): iterable
    {
        $source = $this->sources->create($source);

        if (\count($this->states) === 0) {
            return $this->executeStateless($source, $offset);
        }

        return $this->executeStateful($source, $offset);
    }
}
