<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Position;

/**
 * Providing the ability to get line code from source text.
 */
interface ProvidesLine
{
    /**
     * Returns a line from source code.
     *
     * Note: Returning a type hint is not allowed for compatibility with \Throwable interface.
     *
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @return int
     */
    public function getLine();
}
