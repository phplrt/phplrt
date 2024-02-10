<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

use Phplrt\Compiler\Analyzer;

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

    /**
     * @readonly
     * @psalm-readonly-allow-private-mutation
     */
    protected Analyzer $analyzer;

    public function __construct(Analyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * {@inheritDoc}
     */
    public function withClassReference(string $class, string $alias = null): CodeGeneratorInterface
    {
        $self = clone $this;
        $self->classes[$class] = $alias;

        return $self;
    }

    /**
     * {@inheritDoc}
     */
    public function withFunctionReference(string $function, string $alias = null): CodeGeneratorInterface
    {
        $self = clone $this;
        $self->functions[$function] = $alias;

        return $self;
    }

    /**
     * {@inheritDoc}
     */
    public function withConstReference(string $const, string $alias = null): CodeGeneratorInterface
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
