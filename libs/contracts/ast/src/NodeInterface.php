<?php

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
 *
 * @template-extends \IteratorAggregate<non-empty-string, NodeInterface|array<NodeInterface>>
 */
interface NodeInterface extends \IteratorAggregate
{
    /**
     * Returns the list of children nodes.
     *
     * @return \Traversable<non-empty-string, NodeInterface|array<NodeInterface>>
     */
    public function getIterator(): \Traversable;
}
