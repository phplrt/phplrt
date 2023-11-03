<?php

declare(strict_types=1);

namespace Phplrt\Parser\Context;

interface ContextOptionsProviderInterface
{
    /**
     * Returns arbitrary execution context options which were passed as
     * arguments to the parsing method.
     *
     * @return array<non-empty-string, mixed>
     */
    public function getOptions(): array;

    /**
     * Get the specified option value or $default argument instead.
     *
     * @template TArgDefault of mixed
     *
     * @param non-empty-string $name
     * @param TArgDefault $default
     *
     * @return TArgDefault|mixed
     */
    public function getOption(string $name, $default = null);

    /**
     * Determine if the given option value exists.
     *
     * @param non-empty-string $name
     */
    public function hasOption(string $name): bool;
}
