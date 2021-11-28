<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Trace;

interface FunctionInvocationInterface extends InvocationInterface
{
    /**
     * @return string
     * @psalm-return callable-string
     */
    public function getName(): string;

    /**
     * @return array
     */
    public function getArguments(): array;
}
