<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

/**
 * Interface ContextOptionsInterface
 */
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
