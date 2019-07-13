<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

use Phplrt\Contracts\Ast\LeafInterface;

/**
 * Class Leaf
 */
class Leaf extends Node implements LeafInterface
{
    /**
     * @var string[]
     */
    private $value;

    /**
     * Leaf constructor.
     *
     * @param string $name
     * @param string|string[] $value
     * @param int $offset
     */
    public function __construct(string $name, $value, int $offset = 0)
    {
        parent::__construct($name, $offset);

        $this->value = (array)$value;
    }

    /**
     * @param int $group
     * @return string|null
     */
    public function getValue(int $group = 0): ?string
    {
        return $this->value[$group] ?? null;
    }
}
