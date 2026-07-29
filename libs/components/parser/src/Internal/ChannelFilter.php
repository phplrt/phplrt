<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * TODO Move to a separate "extension point" in the future
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final readonly class ChannelFilter
{
    /**
     * The channels skipped by default
     *
     * @var non-empty-list<ChannelInterface>
     */
    private const array DEFAULT_SKIP_CHANNELS = [
        Channel::Hidden,
    ];

    /**
     * The excluded channels keyed by name, so that every token is a lookup
     * rather than a search through a list.
     *
     * @var array<non-empty-string, true>
     */
    private array $excluded;

    /**
     * @param iterable<mixed, ChannelInterface> $excludedChannels
     */
    public function __construct(
        iterable $excludedChannels = self::DEFAULT_SKIP_CHANNELS,
    ) {
        $excluded = [];

        foreach ($excludedChannels as $channel) {
            $excluded[$channel->name] = true;
        }

        $this->excluded = $excluded;
    }

    /**
     * @template TArgValue of TokenInterface
     * @param iterable<mixed, TArgValue> $tokens
     * @return list<TArgValue>
     */
    public function apply(iterable $tokens): array
    {
        $excluded = $this->excluded;

        // There is nothing to skip, so don't walk over the tokens just to copy
        // them one by one. Hand over what the lexer has already built.
        if ($excluded === [] && \is_array($tokens)) {
            return \array_is_list($tokens) ? $tokens : \array_values($tokens);
        }

        $result = [];

        foreach ($tokens as $token) {
            if (isset($excluded[$token->channel->name])) {
                continue;
            }

            $result[] = $token;
        }

        return $result;
    }
}
