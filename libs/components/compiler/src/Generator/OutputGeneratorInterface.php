<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

use Phplrt\Compiler\CompilerResult;
use Phplrt\Compiler\Exception\GeneratorException;

/**
 * Writes the result of a compilation down as source code.
 *
 * A compiled grammar is nothing but data, so the very same result may be
 * written in any language and any shape: what the code looks like is the
 * business of the generator, while what it recognizes is not.
 */
interface OutputGeneratorInterface
{
    /**
     * @throws GeneratorException in case of the result cannot be written down
     */
    public function generate(CompilerResult $result, OutputContext $context = new OutputContext()): string;
}
