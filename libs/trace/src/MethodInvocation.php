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
use Phplrt\Contracts\Trace\MethodInvocationInterface;

class MethodInvocation extends FunctionInvocation implements MethodInvocationInterface
{
    /**
     * @param class-string $class
     * @param non-empty-string $name
     * @param array $args
     * @param ReadableInterface $source
     * @param PositionInterface $position
     */
    public function __construct(
        ReadableInterface $source,
        PositionInterface $position,
        protected readonly string $class,
        string $name,
        array $args = [],
    ) {
        parent::__construct($source, $position, $name, $args);
    }

    /**
     * @return non-empty-string
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function getName(): string
    {
        return parent::getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getClassName(): string
    {
        return $this->class;
    }

    /**
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    public function getReflection(): \ReflectionMethod
    {
        return new \ReflectionMethod($this->getClassName(), $this->getName());
    }
}
