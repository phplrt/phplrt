<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Filter;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;

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

    public function apply(iterable $tokens): array
    {
        $result = [];

        foreach ($tokens as $token) {
            if (\in_array($token->channel, $this->excludedChannels, true)) {
                continue;
            }

            $result[] = $token;
        }

        return $result;
    }
}
