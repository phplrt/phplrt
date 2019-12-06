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
     * @param string $fqn
     * @return string
     */
    public function generate(string $fqn): string;

    /**
     * @param string $fqn
     * @return string
     */
    public function generateGrammar(string $fqn): string;

    /**
     * @param string $fqn
     * @return string
     */
    public function generateBuilder(string $fqn): string;
}
