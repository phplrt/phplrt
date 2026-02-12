<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Generator;

use Laminas\Code\Generator\ValueGenerator;
use Phplrt\Compiler\Lexer\Definition\AliasedDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;

/**
 * @template-covariant TResult of GeneratedResult = GeneratedResult
 *
 * @template-implements OutputGeneratorInterface<TResult>
 */
abstract readonly class OutputGenerator implements OutputGeneratorInterface
{
    final protected function toGeneratedCode(mixed $value, bool $inline = false): ValueGenerator
    {
        $mode = ValueGenerator::OUTPUT_MULTIPLE_LINE;

        if ($inline) {
            $mode = ValueGenerator::OUTPUT_SINGLE_LINE;
        }

        return new ValueGenerator($value, outputMode: $mode);
    }

    /**
     * @param list<AliasedDefinition> $aliasedDefinitions
     *
     * @return array<non-empty-string, non-empty-string>
     */
    final protected function mapChannels(array $aliasedDefinitions): array
    {
        $result = [];

        foreach ($aliasedDefinitions as $aliased) {
            $channel = $aliased->definition->channel;

            if ($channel === null) {
                continue;
            }

            $result[$aliased->alias] = $channel->value;
        }

        return $result;
    }

    /**
     * @param list<AliasedDefinition> $aliasedDefinitions
     *
     * @return array<non-empty-string, non-empty-string|int>
     */
    final protected function mapAliases(array $aliasedDefinitions): array
    {
        $result = [];
        $index = 0;

        foreach ($aliasedDefinitions as $aliased) {
            $name = $aliased->definition->name;

            if ($name === null) {
                $result[$aliased->alias] = $index++;

                continue;
            }

            $result[$aliased->alias] = $name;
        }

        return $result;
    }
}
