<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Generator;

use Phplrt\Compiler\Lexer\LexerBuilderResult;

/**
 * @template-covariant TResult of GeneratedResult = GeneratedResult
 */
interface OutputGeneratorInterface
{
    /**
     * @return TResult
     */
    public function generate(LexerBuilderResult $result): GeneratedResult;
}
