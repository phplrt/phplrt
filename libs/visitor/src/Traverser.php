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

class Traverser implements TraverserInterface
{
    /**
     * @var VisitorInterface[]
     */
    private array $visitors;

    /**
     * @param array|VisitorInterface[] $visitors
     */
    final public function __construct(array $visitors = [])
    {
        $this->visitors = $visitors;
    }

    /**
     * @param VisitorInterface ...$visitors
     * @return static
     */
    public static function through(VisitorInterface ...$visitors): self
    {
        return new static($visitors);
    }

    /**
     * @param VisitorInterface $visitor
     * @param bool $prepend
     * @return TraverserInterface|Traverser
     */
    public function with(VisitorInterface $visitor, bool $prepend = false): TraverserInterface
    {
        $fn = $prepend ? '\\array_unshift' : '\\array_push';
        $fn($this->visitors, $visitor);

        return $this;
    }

    /**
     * @param VisitorInterface $visitor
     * @return TraverserInterface|Traverser
     */
    public function without(VisitorInterface $visitor): TraverserInterface
    {
        $this->visitors = \array_filter($this->visitors, static function (VisitorInterface $haystack) use ($visitor) {
            return $haystack !== $visitor;
        });

        return $this;
    }

    /**
     * @param iterable|NodeInterface[] $node
     * @return iterable|NodeInterface[]
     */
    public function traverse(iterable $node): iterable
    {
        return (new Executor($this->visitors))->execute($node);
    }
}
