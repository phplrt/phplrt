<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace PHPSTORM_META {

    registerArgumentsSet('phplrt_trace',
        'class',
        'type',
        'function',
        'args',
        'file',
        'line',
        'column'
    );

    registerArgumentsSet('phplrt_trace_items',
        \Phplrt\Exception\Trace\Item::class,
        \Phplrt\Exception\Trace\FunctionItem::class,
        \Phplrt\Exception\Trace\ObjectItem::class
    );

    expectedArguments(\Phplrt\Exception\Trace\Item::fromArray(), 0, argumentsSet('phplrt_trace'));
    expectedArguments(\Phplrt\Exception\Trace\FunctionItem::fromArray(), 0, argumentsSet('phplrt_trace'));
    expectedArguments(\Phplrt\Exception\Trace\ObjectItem::fromArray(), 0, argumentsSet('phplrt_trace'));

    expectedArguments(\Phplrt\Exception\MutableTraceInterface::withTrace(), 0, argumentsSet('phplrt_trace_items'));
}
