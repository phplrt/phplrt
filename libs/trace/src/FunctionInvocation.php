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
use Phplrt\Contracts\Trace\FunctionInvocationInterface;

class FunctionInvocation extends Invocation implements FunctionInvocationInterface
{
    /**
     * @param ReadableInterface $source
     * @param PositionInterface $position
     * @param callable-string $name
     * @param array $args
     */
    public function __construct(
        ReadableInterface $source,
        PositionInterface $position,
        protected readonly string $name,
        protected readonly array $args = [],
    ) {
        parent::__construct($source, $position);
    }

    /**
     * @return callable-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getArguments(): array
    {
        return $this->args;
    }

    /**
     * @return \ReflectionFunctionAbstract
     * @throws \ReflectionException
     */
    public function getReflection(): \ReflectionFunctionAbstract
    {
        return new \ReflectionFunction($this->getName());
    }
}
