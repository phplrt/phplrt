<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Trace;

use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Trace\InvocationInterface;
use Phplrt\Contracts\Trace\TraceInterface;
use Phplrt\Trace\Printer\PhpPrinter;

final class Trace implements TraceInterface, \Stringable
{
    /**
     * @var array<InvocationInterface>
     */
    private array $entries = [];

    /**
     * @param iterable<InvocationInterface> $entries
     */
    public function __construct(iterable $entries = [])
    {
        foreach ($entries as $entry) {
            $this->entries[] = $entry;
        }
    }

    /**
     * @param ReadableInterface $source
     * @param PositionInterface $position
     * @param callable-string $name
     * @param array $args
     * @return void
     */
    public function addFunction(
        ReadableInterface $source,
        PositionInterface $position,
        string $name,
        array $args = []
    ): void {
        $this->add(new FunctionInvocation($source, $position, $name, $args));
    }

    /**
     * @param InvocationInterface $invocation
     * @return void
     */
    public function add(InvocationInterface $invocation): void
    {
        $this->entries[] = $invocation;
    }

    /**
     * @param ReadableInterface $source
     * @param PositionInterface $position
     * @param class-string $class
     * @param non-empty-string $name
     * @param array $args
     * @return void
     */
    public function addMethod(
        ReadableInterface $source,
        PositionInterface $position,
        string $class,
        string $name,
        array $args = [],
    ): void {
        $this->add(new MethodInvocation($source, $position, $class, $name, $args));
    }

    /**
     * @param InvocationInterface $invocation
     * @return void
     */
    public function remove(InvocationInterface $invocation): void
    {
        $this->removeBy(static fn(InvocationInterface $actual): bool => $invocation !== $actual);
    }

    /**
     * @param callable(InvocationInterface):bool $filter
     * @return void
     */
    public function removeBy(callable $filter): void
    {
        $filter = static fn(InvocationInterface $entry): bool => !$filter($entry);

        $this->entries = \array_filter($this->entries, $filter);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(\array_values($this->entries));
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return \count($this->entries);
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return (new PhpPrinter())->print($this);
    }
}
