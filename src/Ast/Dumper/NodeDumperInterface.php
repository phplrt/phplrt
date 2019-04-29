<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast\Dumper;

/**
 * Interface NodeDumperInterface
 */
interface NodeDumperInterface
{
    /**
     * @return string
     */
    public function toString(): string;
}
