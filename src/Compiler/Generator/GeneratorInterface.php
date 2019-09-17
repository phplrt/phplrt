<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

/**
 * Interface GeneratorInterface
 */
interface GeneratorInterface
{
    /**
     * @return string
     */
    public function generate(): string;

    /**
     * @param string $pathname
     * @return void
     */
    public function save(string $pathname): void;
}
