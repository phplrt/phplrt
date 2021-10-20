<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Visitor;

class Traverser implements TraverserInterface
{
    /**
     * @var array<VisitorInterface>
     */
    private array $visitors = [];

    /**
     * Traverser constructor.
     *
     * @param iterable<VisitorInterface> $visitors
     */
    final public function __construct(iterable $visitors = [])
    {
        foreach ($visitors as $visitor) {
            $this->visitors[] = $visitor;
        }
    }

    /**
     * @param VisitorInterface ...$visitors
     * @return static
     */
    public static function create(VisitorInterface ...$visitors): self
    {
        return new static($visitors);
    }

    /**
     * {@inheritDoc}
     */
    public function with(VisitorInterface $visitor, bool $prepend = false): self
    {
        $visitors = $this->visitors;

        $fn = $prepend ? '\\array_unshift' : '\\array_push';
        $fn($visitors, $visitor);

        return new self($visitors);
    }


    /**
     * {@inheritDoc}
     */
    public function without(VisitorInterface $visitor): TraverserInterface
    {
        $filter = static fn (VisitorInterface $haystack): bool => $haystack !== $visitor;
        $visitors = \array_filter($this->visitors, $filter);

        return new self($visitors);
    }

    /**
     * {@inheritDoc}
     */
    public function traverse(iterable $node): iterable
    {
        return (new Executor($this->visitors))->execute($node);
    }
}
