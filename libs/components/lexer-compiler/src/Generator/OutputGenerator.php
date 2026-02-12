<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Generator;

use Laminas\Code\Generator\ValueGenerator;

/**
 * @template-covariant TResult of GeneratedResult = GeneratedResult
 *
 * @template-implements OutputGeneratorInterface<TResult>
 */
abstract class OutputGenerator implements OutputGeneratorInterface
{
    final protected function toGeneratedCode(mixed $value, bool $inline = false): ValueGenerator
    {
        $mode = ValueGenerator::OUTPUT_MULTIPLE_LINE;

        if ($inline) {
            $mode = ValueGenerator::OUTPUT_SINGLE_LINE;
        }

        return new ValueGenerator($value, outputMode: $mode);
    }
}
