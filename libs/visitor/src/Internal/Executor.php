<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor\Internal;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Visitor\Control;
use Phplrt\Visitor\Exception\AttributeException;
use Phplrt\Visitor\Exception\BadMethodException;
use Phplrt\Visitor\Exception\BadReturnTypeException;
use Phplrt\Visitor\Exception\BrokenTreeException;
use Phplrt\Visitor\Exception\VisitorException;
use Phplrt\Visitor\VisitorInterface;

/**
 * This file is part of phplrt package and is a modified/adapted version of
 * "nikic/PHP-parser", which is distributed under the following license:
 *
 * Copyright (c) 2011-2018 by Nikita Popov.
 *
 * Some rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *  * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following disclaimer.
 *
 *  * Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following
 * disclaimer in the documentation and/or other materials provided
 * with the distribution.
 *
 *  * The names of the contributors may not be used to endorse or
 * promote products derived from this software without specific
 * prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @see https://github.com/nikic/PHP-Parser
 * @see https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NodeTraverser.php
 *
 * @internal Phplrt\Visitor\Internal\Executor is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Visitor
 */
class Executor
{
    /**
     * @var string
     */
    private const ERROR_ENTER_RETURN_TYPE =
        '%s::enter() returns an invalid value of type %s';

    /**
     * @var string
     */
    private const ERROR_ENTER_RETURN_ARRAY =
        '%s::enter() cannot modify parent structure, use %s::leave() method instead';

    /**
     * @var string
     */
    private const ERROR_ROOT_REMOVING =
        'visitor::leave() caused the removal of the root node that cannot be deleted';

    /**
     * @var string
     */
    private const ERROR_LEAVE_RETURN_TYPE =
        '%s::leave() returns an invalid value of type %s';

    /**
     * @var string
     */
    private const ERROR_MODIFY_BY_ARRAY =
        '%s::leave() may modify parent structure by an array if the parent is an array';

    /**
     * @var string
     */
    private const ERROR_READONLY_MODIFY =
        'Can not modify the readonly "%s" attribute of %s node';

    /**
     * @var string
     */
    private const ERROR_NESTED_ARRAY =
        'Nested arrays are not a valid traversable AST structure';

    /**
     * @var array|VisitorInterface[]
     */
    private array $visitors;

    /**
     * @var bool
     */
    private bool $stop = false;

    /**
     * @param array $visitors
     */
    public function __construct(array $visitors = [])
    {
        $this->visitors = $visitors;
    }

    /**
     * Traverses an array of nodes using the registered visitors.
     *
     * @param iterable<NodeInterface> $ast Array of nodes
     * @return iterable<NodeInterface> Traversed array of nodes
     */
    public function execute(iterable $ast): iterable
    {
        $this->stop = false;

        $ast = $this->before($ast);
        $ast = $this->each($ast);
        $ast = $this->after($ast);

        return $ast;
    }

    /**
     * @param iterable<NodeInterface> $ast
     * @return iterable<NodeInterface>
     */
    private function before(iterable $ast): iterable
    {
        foreach ($this->visitors as $visitor) {
            if (($result = $visitor->before($ast)) !== null) {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $ast = $result;
            }
        }

        return $ast;
    }

    /**
     * @param iterable|\Traversable|array $ast
     * @return iterable
     */
    private function each(iterable $ast): iterable
    {
        switch (true) {
            case $ast instanceof NodeInterface:
                $result = $this->traverseArray([$ast]);

                if (($first = \reset($result)) === false) {
                    throw new BadMethodException(self::ERROR_ROOT_REMOVING);
                }

                return $first;

            case \is_array($ast):
                return $this->traverseArray($ast);

            default:
                return $this->traverseArray(\iterator_to_array($ast, false));
        }
    }

    /**
     * Recursively traverse array (usually of nodes).
     *
     * @param array $nodes Array to traverse
     * @return array Result of traversal (may be original array or changed one)
     */
    protected function traverseArray(array $nodes): array
    {
        $replacements = [];

        foreach ($nodes as $i => &$node) {
            if ($node instanceof NodeInterface) {
                $traverseChildren = true;
                $breakVisitorIndex = null;

                foreach ($this->visitors as $index => $visitor) {
                    $return = $visitor->enter($node);

                    switch (true) {
                        case $return === null:
                            break;

                        case $return instanceof NodeInterface:
                            $node = $return;
                            break;

                        case $return === Control::DONT_TRAVERSE_CHILDREN:
                            $traverseChildren = false;
                            break;

                        case $return === Control::DONT_TRAVERSE_CURRENT_AND_CHILDREN:
                            $traverseChildren = false;
                            $breakVisitorIndex = $index;
                            break 2;

                        case $return === Control::STOP_TRAVERSAL:
                            $this->stop = true;
                            break 3;

                        case \is_array($return):
                            $error = self::ERROR_ENTER_RETURN_ARRAY;
                            $error = \sprintf($error, \get_class($visitor), \gettype($visitor));

                            throw new BadMethodException($error, VisitorException::ERROR_CODE_ARRAY_ENTERING);

                        default:
                            $error = self::ERROR_ENTER_RETURN_TYPE;
                            $error = \sprintf($error, \get_class($visitor), \gettype($visitor));

                            throw new BadReturnTypeException($error, VisitorException::ERROR_CODE_ARRAY_ENTERING);
                    }
                }

                if ($traverseChildren) {
                    $node = $this->traverseNode($node);

                    if ($this->stop) {
                        break;
                    }
                }

                foreach ($this->visitors as $index => $visitor) {
                    $return = $visitor->leave($node);

                    switch (true) {
                        case $return === null:
                            break;

                        case $return instanceof NodeInterface:
                            $node = $return;
                            break;

                        case \is_array($return):
                            $replacements[] = [$i, $return];
                            break 2;

                        case $return === Control::REMOVE_NODE:
                            $replacements[] = [$i, []];
                            break 2;

                        case $return === Control::STOP_TRAVERSAL:
                            $this->stop = true;
                            break 3;

                        default:
                            $error = self::ERROR_LEAVE_RETURN_TYPE;
                            $error = \sprintf($error, \get_class($visitor), \gettype($return));

                            throw new BadReturnTypeException($error, VisitorException::ERROR_CODE_ARRAY_LEAVING);
                    }

                    if ($breakVisitorIndex === $index) {
                        break;
                    }
                }
            } elseif (\is_array($node)) {
                throw new BrokenTreeException(self::ERROR_NESTED_ARRAY);
            }
        }

        if (! empty($replacements)) {
            while ([$i, $replace] = \array_pop($replacements)) {
                \array_splice($nodes, $i, 1, $replace);
            }
        }

        return $nodes;
    }

    /**
     * Recursively traverse a node.
     *
     * @param NodeInterface $node NodeInterface to traverse.
     * @return NodeInterface Result of traversal (may be original node or new one)
     */
    protected function traverseNode(NodeInterface $node): NodeInterface
    {
        foreach ($node as $name => $child) {
            if (\is_array($child)) {
                $this->updateNodeValue($node, $name, $this->traverseArray($child));

                if ($this->stop) {
                    return $node;
                }
            } elseif ($child instanceof NodeInterface) {
                do {
                    $loop = false;
                    $traverseChildren = true;
                    $breakVisitorIndex = null;

                    foreach ($this->visitors as $index => $visitor) {
                        $return = $visitor->enter($child);

                        switch (true) {
                            case $return === null:
                                break;

                            case $return === Control::DONT_TRAVERSE_CHILDREN:
                                $traverseChildren = false;
                                break;

                            case $return === Control::DONT_TRAVERSE_CURRENT_AND_CHILDREN:
                                $traverseChildren = false;
                                $breakVisitorIndex = $index;
                                break 3;

                            case $return === Control::STOP_TRAVERSAL:
                                $this->stop = true;
                                break 4;

                            default:
                                $error = self::ERROR_ENTER_RETURN_TYPE;
                                $error = \sprintf($error, \get_class($visitor), \gettype($return));

                                throw new BadReturnTypeException($error, VisitorException::ERROR_CODE_NODE_ENTERING);
                        }
                    }

                    if ($traverseChildren) {
                        $this->updateNodeValue($node, $name, $child = $this->traverseNode($child));

                        if ($this->stop) {
                            break 2;
                        }
                    }

                    foreach ($this->visitors as $index => $visitor) {
                        $return = $visitor->leave($child);

                        switch (true) {
                            case $return === null:
                                break;

                            case $return instanceof NodeInterface:
                                $this->updateNodeValue($node, $name, $child = $return);
                                break;

                            case $return === Control::STOP_TRAVERSAL:
                                $this->stop = true;
                                break 4;

                            case $return === Control::LOOP_ON_CURRENT:
                                $loop = true;
                                break;

                            case \is_array($return):
                                $error = self::ERROR_MODIFY_BY_ARRAY;
                                $error = \sprintf($error, \get_class($visitor));

                                throw new BadReturnTypeException($error, VisitorException::ERROR_CODE_NODE_LEAVING);

                            default:
                                $error = self::ERROR_LEAVE_RETURN_TYPE;
                                $error = \sprintf($error, \get_class($visitor), \gettype($return));

                                throw new BadReturnTypeException($error, VisitorException::ERROR_CODE_NODE_LEAVING);
                        }

                        if ($breakVisitorIndex === $index) {
                            break;
                        }
                    }
                } while ($loop);
            }
        }

        return $node;
    }

    /**
     * @param NodeInterface $node
     * @param string|int $key
     * @param mixed $value
     * @return void
     */
    private function updateNodeValue(NodeInterface $node, $key, $value): void
    {
        try {
            $node->$key = $value;
        } catch (\Error $e) {
            if (\strpos($e->getMessage(), 'Cannot access') !== 0) {
                throw $e;
            }

            $error = self::ERROR_READONLY_MODIFY;
            $error = \sprintf($error, $key, \get_class($node));

            throw new AttributeException($error);
        }
    }

    /**
     * @param iterable $ast
     * @return iterable
     */
    private function after(iterable $ast): iterable
    {
        foreach ($this->visitors as $visitor) {
            if (($result = $visitor->after($ast)) !== null) {
                $ast = $result;
            }
        }

        return $ast;
    }
}