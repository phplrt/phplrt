<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Lexer\Token\Channel;
use Phplrt\Lexer\Token\Token;

final class LexerCreateInfo
{
    /**
     * @var array<non-empty-string, ChannelInterface>
     */
    public const DEFAULT_CHANNELS = [
        Token::NAME_UNKNOWN => Channel::UNKNOWN,
        Token::NAME_EOI => Channel::END_OF_INPUT,
    ];

    /**
     * @var array<ChannelInterface>
     */
    public const DEFAULT_THROW_CHANNELS = [
        Channel::UNKNOWN,
    ];

    /**
     * @var bool
     */
    public readonly bool $debug;

    /**
     * @var array<non-empty-string|int, non-empty-string>
     */
    public readonly array $tokens;

    /**
     * @var array<non-empty-string|int, ChannelInterface>
     */
    public readonly array $channels;

    /**
     * List of channels whose tokens interrupt the lexer and throw an exception.
     *
     * @var array<ChannelInterface>
     */
    public readonly array $throw;

    /**
     * @param iterable<non-empty-string|int, non-empty-string> $tokens
     * @param iterable<non-empty-string> $skip
     * @param bool|null $debug
     * @param non-empty-string $unknownTokenName
     * @param non-empty-string $eoiTokenName
     * @param iterable<non-empty-string|int, ChannelInterface|non-empty-string> $channels
     * @param iterable<ChannelInterface|non-empty-string> $throw
     * @param RegExpCreateInfo $pcre
     */
    public function __construct(
        iterable $tokens = [],
        iterable $skip = [],
        iterable $channels = self::DEFAULT_CHANNELS,
        public readonly string $unknownTokenName = Token::NAME_UNKNOWN,
        public readonly string $eoiTokenName = Token::NAME_EOI,
        iterable $throw = self::DEFAULT_THROW_CHANNELS,
        ?bool $debug = null,
        public readonly RegExpCreateInfo $pcre = new RegExpCreateInfo(),
    ) {
        $this->tokens = $this->formatTokens($tokens);
        $this->debug = $this->formatDebug($debug);
        $this->channels = $this->formatWithHiddenChannels($channels, $skip);
        $this->throw = $this->formatChannels($throw);
    }

    /**
     * @param iterable $channels
     * @param iterable $skip
     * @return array<ChannelInterface>
     *
     * @psalm-suppress MixedArgumentTypeCoercion
     * @psalm-suppress MixedArrayOffset
     * @psalm-suppress MixedAssignment
     */
    private function formatWithHiddenChannels(iterable $channels, iterable $skip): array
    {
        $channels = $this->formatChannels($channels);

        foreach ($skip as $name) {
            $channels[$name] = Channel::HIDDEN;
        }

        return $channels;
    }

    /**
     * @param bool|null $debug
     * @return bool
     */
    private function formatDebug(?bool $debug): bool
    {
        if ($debug === null) {
            /**
             * Enable debug mode if "zend.assertions" is 1.
             * @link https://www.php.net/manual/en/function.assert.php
             */
            assert($debug = true);
        }

        return (bool)$debug;
    }

    /**
     * @param iterable<non-empty-string|int, non-empty-string> $tokens
     * @return array<non-empty-string|int, non-empty-string>
     */
    private function formatTokens(iterable $tokens): array
    {
        $result = [];

        foreach ($tokens as $token => $pattern) {
            $result[$token] = $pattern;
        }

        $result[$this->unknownTokenName] = '.+?';

        return $result;
    }

    /**
     * @param iterable<ChannelInterface|non-empty-string> $channels
     * @return array<ChannelInterface>
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayOffset
     */
    private function formatChannels(iterable $channels): array
    {
        $result = [];

        foreach ($channels as $i => $channel) {
            if (\is_string($channel)) {
                $channel = Channel::create($channel);
            }

            $result[$i] = $channel;
        }

        return $result;
    }
}
