<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Trace;

/**
 * @template-extends \IteratorAggregate<positive-int|0, InvocationInterface>
 */
interface TraceInterface extends \IteratorAggregate, \Countable
{
}
