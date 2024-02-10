<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

interface GeneratableInterface extends \Stringable
{
    /**
     * Generates code result as string.
     */
    public function generate(): string;
}
