<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

interface GeneratorInterface extends \Stringable
{
    /**
     * Generates code result as string.
     */
    public function generate(): string;
}
