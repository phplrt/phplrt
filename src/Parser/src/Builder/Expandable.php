<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Builder;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Class Expandable
 */
class Expandable implements BuilderInterface
{
    /**
     * @var array|\Closure[]
     */
    public $reducers = [];

    /**
     * Extendable constructor.
     *
     * @param array|\Closure[] $reducers
     */
    public function __construct(array $reducers = [])
    {
        $this->reducers = $reducers;
    }

    /**
     * @param array $reducers
     * @return Expandable|$this
     */
    public function extend(array $reducers): self
    {
        $this->reducers = \array_merge($this->reducers, $reducers);

        return $this;
    }

    /**
     * @param string|int $state
     * @param \Closure $then
     * @return Expandable|$this
     */
    public function on($state, \Closure $then): self
    {
        $this->reducers[$state] = $then;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function build(ReadableInterface $file, RuleInterface $rule, TokenInterface $token, $state, $children)
    {
        if (isset($this->reducers[$state])) {
            return $this->reducers[$state]($children, $token->getOffset(), $state, $file);
        }

        return null;
    }
}
