<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Extractor;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Interface ExtractorInterface
 */
interface ExtractorInterface
{
    /**
     * Registers a class, interface or trait in the system under the
     * selected name.
     *
     * This means that in the export all dependencies with the selected names
     * will be automatically renamed to the specified name.
     *
     * @param string $class
     * @param string $as
     * @return void
     */
    public function import(string $class, string $as): void;

    /**
     * Gets the source code of a class, interface or trait with automatic
     * resolving of names of all dependencies.
     *
     * The second argument indicates under which name the specified class
     * will be exported to the system.
     *
     * @param string $name
     * @param string|null $as
     * @return string
     */
    public function extract(string $name, string $as = null): string;

    /**
     * Gets the source code with automatic resolving of names of all
     * dependencies.
     *
     * The second argument indicates under which name the specified class
     * will be exported to the system.
     *
     * @param string|resource|ReadableInterface $source
     * @param string|null $as
     * @return string
     */
    public function extractSource($source, string $as = null): string;
}
