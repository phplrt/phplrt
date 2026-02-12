<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\LexerBuilder;

final readonly class RemoveUnusedChannelsLexerCompilerPass implements LexerCompilerPassInterface
{
    public function process(LexerBuilder $builder): void
    {
        $used = $this->getUsedChannels($builder);

        foreach ($builder->channels as $definition) {
            if (isset($used[$definition->value])) {
                continue;
            }

            $builder->removeChannelDefinition($definition);
        }
    }

    /**
     * @return array<non-empty-string, true>
     */
    private function getUsedChannels(LexerBuilder $builder): array
    {
        $used = [];

        foreach ($builder->tokens as $definition) {
            if ($definition->channel === null) {
                continue;
            }

            $used[$definition->channel->value] = true;
        }

        return $used;
    }
}
