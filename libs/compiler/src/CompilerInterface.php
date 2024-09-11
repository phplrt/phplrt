<?php

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Context\CompilerContext;
use Phplrt\Compiler\Generator\CodeGeneratorInterface;

interface CompilerInterface
{
    /**
     * Loads a custom grammar source into the compiler.
     *
     * @return $this
     */
    public function load(mixed $source): self;

    /**
     * Returns loaded context.
     */
    public function getContext(): CompilerContext;

    /**
     * Builds grammar and creates a code generator.
     */
    public function build(): CodeGeneratorInterface;
}
