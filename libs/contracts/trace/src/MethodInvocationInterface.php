<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Trace;

interface MethodInvocationInterface extends FunctionInvocationInterface
{
    /**
     * @return string
     * @psalm-return class-string
     */
    public function getClassName(): string;

    /**
     * @return string
     * @psalm-return non-empty-string
     */
    public function getName(): string;
}
