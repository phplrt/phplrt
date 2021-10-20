<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Stub;

use Phplrt\Contracts\Grammar\RuleInterface;

class Rule implements RuleInterface
{
    public static function new(): self
    {
        return new self();
    }
}
