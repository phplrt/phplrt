<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

use Phplrt\Compiler\Context\CompilerContext;

abstract class CodeGenerator implements CodeGeneratorInterface
{
    /**
     * @var array<non-empty-string, non-empty-string|null>
     */
    protected array $classes = [];

    /**
     * @var array<non-empty-string, non-empty-string|null>
     */
    protected array $functions = [];

    /**
     * @var array<non-empty-string, non-empty-string|null>
     */
    protected array $constants = [];

    public function __construct(
        protected readonly CompilerContext $analyzer,
    ) {}

    public function withClassReference(string $class, ?string $alias = null): CodeGeneratorInterface
    {
        $self = clone $this;
        $self->classes[$class] = $alias;

        return $self;
    }

    public function withFunctionReference(string $function, ?string $alias = null): CodeGeneratorInterface
    {
        $self = clone $this;
        $self->functions[$function] = $alias;

        return $self;
    }

    public function withConstReference(string $const, ?string $alias = null): CodeGeneratorInterface
    {
        $self = clone $this;
        $self->constants[$const] = $alias;

        return $self;
    }

    public function __toString(): string
    {
        return $this->generate();
    }
}
