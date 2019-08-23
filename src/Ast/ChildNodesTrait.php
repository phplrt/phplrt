<?php
/**
 * This file is part of ast package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

use Phplrt\Contracts\Ast\NodeInterface;

/**
 * Trait ChildNodesTrait
 */
trait ChildNodesTrait
{
    /**
     * @var array|string[]
     */
    protected $childNodeNames;

    /**
     * @return array|string[]
     */
    protected function getChildNodeNames(): array
    {
        if ($this->childNodeNames === null) {
            $this->childNodeNames = \iterator_to_array($this->readChildNodeNames());
        }

        return $this->childNodeNames;
    }

    /**
     * @return \Generator|string[]
     */
    private function readChildNodeNames(): \Generator
    {
        $reflection = new \ReflectionObject($this);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getValue($this) instanceof NodeInterface) {
                yield $property->getName();
            }
        }
    }
}
