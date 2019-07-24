<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\NameResolver\Resolver;

use PhpParser\Node\Name;

/**
 * Class Booleans
 */
class Booleans implements ResolverInterface
{
    /**
     * @var string[]
     */
    private const VALUES = [
        'true',
        'false'
    ];

    /**
     * @param Name $name
     * @param \Closure $export
     * @return Name|null
     */
    public function resolve(Name $name, \Closure $export): ?Name
    {
        if (\count($name->parts) !== 1) {
            return null;
        }

        $lower = \strtolower($name->getFirst());

        return \in_array($lower, self::VALUES, true) ? $name : null;
    }
}
