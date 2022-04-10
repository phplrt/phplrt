<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser;

/**
 * @psalm-type Reducer = \Closure(ContextInterface, mixed): mixed
 */
final class SimpleBuilder implements BuilderInterface
{
    /**
     * @var array<array-key, Reducer>
     */
    private array $reducers;

    /**
     * @param array<array-key, Reducer> $reducers
     */
    public function __construct(
        array $reducers = []
    ) {
        $this->reducers = $reducers;
    }

    /**
     * {@inheritDoc}
     */
    public function build(ContextInterface $context, $result)
    {
        $state = $context->getState();

        if (isset($this->reducers[$state])) {
            return ($this->reducers[$state])($context, $result);
        }

        return $result;
    }
}
