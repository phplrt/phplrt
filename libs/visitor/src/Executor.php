<?php

/**
 * This file is part of phplrt package and is a modified/adapted version of
 * "nikic/PHP-Parser", which is distributed under the following license:
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
 */

declare(strict_types=1);

namespace Phplrt\Visitor;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Visitor\Exception\AttributeException;
use Phplrt\Visitor\Exception\BadMethodException;
use Phplrt\Visitor\Exception\BadReturnTypeException;
use Phplrt\Visitor\Exception\BrokenTreeException;

class Executor implements ExecutorInterface
{
    /**
     * @var int
     */
    public const ERROR_CODE_ARRAY_ENTERING = 0x01;

    /**
     * @var int
     */
    public const ERROR_CODE_ARRAY_LEAVING = 0x02;

    /**
     * @var int
     */
    public const ERROR_CODE_NODE_ENTERING = 0x03;

    /**
     * @var int
     */
    public const ERROR_CODE_NODE_LEAVING = 0x04;

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
        'Visitor::leave() caused the removal of the root node that cannot be deleted';

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

    private bool $stop = false;

    public function __construct(
        /**
         * @var array|VisitorInterface[]
         */
        private array $visitors = []
    ) {}

    /**
     * @param iterable<array-key, object> $nodes
     *
     * @return iterable<array-key, object>
     */
    public function execute(iterable $nodes): iterable
    {
        $this->stop = false;

        $nodes = $this->before($nodes);
        $nodes = $this->each($nodes);

        return $this->after($nodes);
    }

    /**
     * @param iterable<array-key, object> $nodes
     *
     * @return iterable<array-key, object>
     */
    private function before(iterable $nodes): iterable
    {
        foreach ($this->visitors as $visitor) {
            if (($result = $visitor->before($nodes)) !== null) {
                $nodes = $result;
            }
        }

        return $nodes;
    }

    /**
     * @param iterable<array-key, object> $nodes
     *
     * @return iterable<array-key, object>
     */
    private function each(iterable $nodes): iterable
    {
        if ($nodes instanceof NodeInterface) {
            $result = $this->traverseArray([$nodes]);
            /** @var NodeInterface|false $first */
            $first = \reset($result);

            if ($first === false) {
                throw new BadMethodException(self::ERROR_ROOT_REMOVING);
            }

            /** @var iterable<array-key, object> */
            return $first;
        }

        if (\is_array($nodes)) {
            return $this->traverseArray($nodes);
        }

        return $this->traverseArray(\iterator_to_array($nodes, false));
    }

    /**
     * Recursively traverse array (usually of nodes).
     *
     * @param array<array-key, object> $nodes Array to traverse
     *
     * @return array<array-key, object> Result of traversal. May be original
     *         array or changed one.
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

                        case $return === TraverserInterface::DONT_TRAVERSE_CHILDREN:
                            $traverseChildren = false;
                            break;

                        case $return === TraverserInterface::DONT_TRAVERSE_CURRENT_AND_CHILDREN:
                            $traverseChildren = false;
                            $breakVisitorIndex = $index;
                            break 2;

                        case $return === TraverserInterface::STOP_TRAVERSAL:
                            $this->stop = true;
                            break 3;

                        case \is_array($return):
                            $error = self::ERROR_ENTER_RETURN_ARRAY;
                            $error = \sprintf($error, $visitor::class, \gettype($visitor));

                            throw new BadMethodException($error, static::ERROR_CODE_ARRAY_ENTERING);
                        default:
                            $error = self::ERROR_ENTER_RETURN_TYPE;
                            $error = \sprintf($error, $visitor::class, \gettype($visitor));

                            throw new BadReturnTypeException($error, static::ERROR_CODE_ARRAY_ENTERING);
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

                        case $return === TraverserInterface::REMOVE_NODE:
                            $replacements[] = [$i, []];
                            break 2;

                        case $return === TraverserInterface::STOP_TRAVERSAL:
                            $this->stop = true;
                            break 3;

                        default:
                            $error = self::ERROR_LEAVE_RETURN_TYPE;
                            $error = \sprintf($error, $visitor::class, \gettype($return));

                            throw new BadReturnTypeException($error, static::ERROR_CODE_ARRAY_LEAVING);
                    }

                    if ($breakVisitorIndex === $index) {
                        break;
                    }
                }
            } elseif (\is_array($node)) {
                throw new BrokenTreeException(self::ERROR_NESTED_ARRAY);
            }
        }

        if ($replacements !== []) {
            while ([$i, $replace] = \array_pop($replacements)) {
                \array_splice($nodes, $i, 1, $replace);
            }
        }

        return $nodes;
    }

    /**
     * Recursively traverse a node.
     *
     * @param NodeInterface $node nodeInterface to traverse
     *
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

                            case $return === TraverserInterface::DONT_TRAVERSE_CHILDREN:
                                $traverseChildren = false;
                                break;

                            case $return === TraverserInterface::DONT_TRAVERSE_CURRENT_AND_CHILDREN:
                                $traverseChildren = false;
                                $breakVisitorIndex = $index;
                                break 3;

                            case $return === TraverserInterface::STOP_TRAVERSAL:
                                $this->stop = true;
                                break 4;

                            default:
                                $error = self::ERROR_ENTER_RETURN_TYPE;
                                $error = \sprintf($error, $visitor::class, \gettype($return));

                                throw new BadReturnTypeException($error, static::ERROR_CODE_NODE_ENTERING);
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

                            case $return === TraverserInterface::STOP_TRAVERSAL:
                                $this->stop = true;
                                break 4;

                            case $return === TraverserInterface::LOOP_ON_CURRENT:
                                $loop = true;
                                break;

                            case \is_array($return):
                                $error = self::ERROR_MODIFY_BY_ARRAY;
                                $error = \sprintf($error, $visitor::class);

                                throw new BadReturnTypeException($error, static::ERROR_CODE_NODE_LEAVING);
                            default:
                                $error = self::ERROR_LEAVE_RETURN_TYPE;
                                $error = \sprintf($error, $visitor::class, \gettype($return));

                                throw new BadReturnTypeException($error, static::ERROR_CODE_NODE_LEAVING);
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

    private function updateNodeValue(NodeInterface $node, int|string $key, mixed $value): void
    {
        try {
            // @phpstan-ignore-next-line
            $node->$key = $value;
        } catch (\Error $e) {
            if (!\str_starts_with($e->getMessage(), 'Cannot access')) {
                throw $e;
            }

            $error = self::ERROR_READONLY_MODIFY;
            $error = \sprintf($error, $key, $node::class);

            throw new AttributeException($error);
        }
    }

    /**
     * @param iterable<array-key, object> $nodes
     *
     * @return iterable<array-key, object>
     */
    private function after(iterable $nodes): iterable
    {
        foreach ($this->visitors as $visitor) {
            if (($result = $visitor->after($nodes)) !== null) {
                $nodes = $result;
            }
        }

        return $nodes;
    }
}
