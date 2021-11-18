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
use Phplrt\Lexer\PCRE\RegExpCreateInfo;
use Phplrt\Lexer\Token\Channel;
use Phplrt\Lexer\Token\Token;

/**
 * @psalm-type TName = non-empty-string|int
 * @psalm-type TPattern = non-empty-string
 * @psalm-type TChannelName = non-empty-string
 * @psalm-type TChannel = TChannelName|ChannelInterface
 */
final class LexerCreateInfo
{
    /**
     * @var bool
     */
    public readonly bool $debug;

    /**
     * @var array<TName, TPattern>
     */
    public readonly array $tokens;

    /**
     * @var array<TName, TChannelName>
     */
    public readonly array $channels;

    /**
     * @param iterable<TName, TPattern> $tokens
     * @param iterable<TName> $skip
     * @param iterable<TName, TChannel> $channels
     * @param bool|null $debug
     * @param RegExpCreateInfo $pcre
     */
    public function __construct(
        iterable $tokens,
        iterable $skip = [],
        iterable $channels = [],
        ?bool $debug = null,
        public readonly RegExpCreateInfo $pcre = new RegExpCreateInfo(),
    ) {
        $this->tokens = $this->formatTokens($tokens);
        $this->channels = $this->formatChannels($skip, $channels);
        $this->debug = $this->formatDebug($debug);
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
     * @param iterable<TName, TChannel> $channels
     * @param iterable<TName, TChannelName> $skip
     * @return array<TName, TChannelName>
     */
    private function formatChannels(iterable $skip, iterable $channels): array
    {
        $result = [];

        foreach ($channels as $token => $channel) {
            if ($channel instanceof ChannelInterface) {
                $channel = $channel->getName();
            }

            $result[$token] = $channel;
        }

        $hidden = Channel::HIDDEN->getName();
        foreach ($skip as $token) {
            $result[$token] = $hidden;
        }

        $result[Token::NAME_UNKNOWN] = Channel::UNKNOWN;

        return $result;
    }

    /**
     * @param iterable<TName, TPattern> $tokens
     * @return array<TName, TPattern>
     */
    private function formatTokens(iterable $tokens): array
    {
        $result = [];

        foreach ($tokens as $token => $pattern) {
            $result[$token] = $pattern;
        }

        $result[Token::NAME_UNKNOWN] = '.+?';

        return $result;
    }
}
