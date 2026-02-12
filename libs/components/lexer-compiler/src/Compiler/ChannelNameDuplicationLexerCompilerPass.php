<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Compiler;

use Phplrt\Compiler\Lexer\Exception\CompilationFailedException;
use Phplrt\Compiler\Lexer\LexerBuilder;

final readonly class ChannelNameDuplicationLexerCompilerPass implements LexerCompilerPassInterface
{
    public function process(LexerBuilder $builder): void
    {
        $channels = [];

        foreach ($builder->tokens as $definition) {
            $channel = $definition->channel;

            if ($channel === null) {
                continue;
            }

            $name = $channel->value;

            if (isset($channels[$name]) && $channels[$name] !== $channel) {
                throw new CompilationFailedException(\sprintf(
                    'Another channel "%s" for token %s with the same name is already registered',
                    $name,
                    $definition,
                ));
            }

            $channels[$name] = $channel;
        }
    }
}
