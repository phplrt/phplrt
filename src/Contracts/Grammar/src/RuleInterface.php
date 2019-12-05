<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Grammar;

/**
 * The base interface of all parser rules.
 */
interface RuleInterface
{
    /**
     * Returns an array of constructor arguments for subsequent generation of
     * the rule using compiler-compilers.
     *
     * For example:
     *
     * <code>
     *  $token = new Token('\d+', true);
     *  $token->getConstructorArguments();
     *  //
     *  // Expected Output:
     *  // >  array(2) { '\d+', true }
     *  //
     * </code>
     *
     * @return array
     */
    public function getConstructorArguments(): array;
}
