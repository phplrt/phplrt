<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Ast;

/**
 * An abstract node representation. Each node of the tree denotes a
 * construct occurring in the source code.
 *
 * The NodeInterface provided by an abstract syntax trees (AST) are
 * data structures widely used in compilers to represent the structure of
 * program code. An AST is usually the result of the syntax analysis phase
 * of a compiler.
 *
 * It often serves as an intermediate representation of the program
 * through several stages that the parser\compiler requires, and has a
 * strong impact on the final output of the parser\compiler.
 */
interface NodeInterface extends \IteratorAggregate
{
    /**
     * Returns offset in bytes the node started in.
     *
     * @return int
     */
    public function getOffset(): int;

    /**
     * Returns the list of children nodes.
     *
     * @see \IteratorAggregate::getIterator()
     * @return \Traversable|NodeInterface[]
     */
    public function getIterator(): \Traversable;
}
