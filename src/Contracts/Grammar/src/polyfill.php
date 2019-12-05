<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Parser\Rule\TerminalInterface;
use Phplrt\Parser\Rule\ProductionInterface;

/**
 * "Phplrt\Parser\Rule\***" rules deprecated since version 2.3
 * and will be removed in 3.0.
 */
foreach (
    [
        \Phplrt\Contracts\Grammar\RuleInterface::class       => RuleInterface::class,
        \Phplrt\Contracts\Grammar\ProductionInterface::class => ProductionInterface::class,
        \Phplrt\Contracts\Grammar\TerminalInterface::class   => TerminalInterface::class,
    ] as $source => $alias
) {
    if (! \interface_exists($alias)) {
        \class_alias($source, $alias);
    }
}
