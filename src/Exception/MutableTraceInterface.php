<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Exception\Trace\FunctionItemInterface;
use Phplrt\Exception\Trace\ItemInterface;
use Phplrt\Exception\Trace\ObjectItemInterface;

/**
 * Interface MutableTraceInterface
 */
interface MutableTraceInterface
{
    /**
     * @param ItemInterface|FunctionItemInterface|ObjectItemInterface $item
     * @return ItemInterface|FunctionItemInterface|ObjectItemInterface
     */
    public function withTrace(ItemInterface $item): ItemInterface;
}
