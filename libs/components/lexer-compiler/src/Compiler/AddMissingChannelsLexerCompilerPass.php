<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilder;
use Phplrt\Contracts\Lexer\ChannelInterface;

final readonly class AddMissingChannelsLexerCompilerPass implements LexerCompilerPassInterface
{
    public function process(LexerBuilder $builder): void
    {
        $used = $this->getUsedChannels($builder);

        foreach ($used as $name => $definition) {
            if (!\in_array($definition, $builder->channels, true)) {
                $builder->channel($name);
            }
        }
    }

    /**
     * @return array<non-empty-string, ChannelInterface>
     */
    private function getUsedChannels(LexerBuilder $builder): array
    {
        $used = [];

        foreach ($builder->tokens as $tokenDefinition) {
            $channel = $tokenDefinition->channel;

            if ($channel === null) {
                continue;
            }

            $used[$channel->value] = $channel;
        }

        return $used;
    }
}
