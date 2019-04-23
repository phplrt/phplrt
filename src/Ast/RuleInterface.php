<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

/**
 * Interface RuleInterface
 */
interface RuleInterface extends NodeInterface, \Countable, \IteratorAggregate
{
    /**
     * @return iterable|NodeInterface[]|RuleInterface[]|LeafInterface[]
     */
    public function getChildren(): iterable;

    /**
     * @param int $index
     * @return NodeInterface|RuleInterface|LeafInterface|mixed
     */
    public function getChild(int $index);

    /**
     * @param string $name
     * @param int|null $depth
     * @return mixed
     */
    public function first(string $name, int $depth = null);

    /**
     * @param string $name
     * @param int|null $depth
     * @return iterable
     */
    public function find(string $name, int $depth = null): iterable;
}
