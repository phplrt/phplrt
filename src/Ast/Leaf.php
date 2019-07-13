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
     * @var string
     */
    private $value;

    /**
     * Leaf constructor.
     *
     * @param string $name
     * @param string $value
     * @param int $offset
     */
    public function __construct(string $name, string $value, int $offset = 0)
    {
        parent::__construct($name, $offset);

        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
