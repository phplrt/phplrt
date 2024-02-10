<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

interface CodeGeneratorInterface extends GeneratableInterface
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string|null $alias
     *
     * @psalm-immutable
     */
    public function withClassReference(string $class, string $alias = null): self;

    /**
     * @param non-empty-string $function
     * @param non-empty-string|null $alias
     *
     * @psalm-immutable
     */
    public function withFunctionReference(string $function, string $alias = null): self;

    /**
     * @param non-empty-string $const
     * @param non-empty-string|null $alias
     *
     * @psalm-immutable
     */
    public function withConstReference(string $const, string $alias = null): self;
}
