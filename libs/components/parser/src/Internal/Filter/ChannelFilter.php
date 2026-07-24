<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Filter;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

final readonly class ChannelFilter implements FilterInterface
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
     * @var list<ChannelInterface>
     */
    private array $excludedChannels;

    /**
     * @param iterable<mixed, ChannelInterface> $excludedChannels
     */
    public function __construct(
        iterable $excludedChannels = self::DEFAULT_SKIP_CHANNELS,
    ) {
        $this->excludedChannels = \iterator_to_array($excludedChannels, false);
    }

    /**
     * @template TArgValue of TokenInterface
     * @param iterable<mixed, TArgValue> $tokens
     * @return list<TArgValue>
     */
    public function apply(iterable $tokens): array
    {
        $excluded = $this->excludedChannels;
        $result = [];

        foreach ($tokens as $token) {
            if (\in_array($token->channel, $excluded, true)) {
                continue;
            }

            $result[] = $token;
        }

        return $result;
    }
}
