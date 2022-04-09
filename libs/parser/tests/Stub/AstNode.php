<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Stub;

use Phplrt\Contracts\Ast\NodeInterface;

class AstNode implements NodeInterface
{
    public string $name;
    public array $children;

    public function __construct(string $name, array $children = [])
    {
        $this->name = $name;
        $this->children = $children;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children);
    }

    public function __set($name, $value)
    {
        $this->children[$name] = $value;
    }
}
