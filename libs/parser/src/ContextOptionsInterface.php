<?php

declare(strict_types=1);

namespace Phplrt\Parser;

interface ContextOptionsInterface
{
    /**
     * Returns arbitrary execution context options which were passed as
     * arguments to the parsing method.
     *
     * @return array
     */
    public function getOptions(): array;

    /**
     * Get the specified option value or $default argument instead.
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getOption(string $name, $default = null);

    /**
     * Determine if the given option value exists.
     *
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool;
}
