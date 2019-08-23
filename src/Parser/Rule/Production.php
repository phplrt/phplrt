<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class Production
 */
abstract class Production extends Rule implements ProductionInterface
{
    /**
     * @var \Closure
     */
    private $reducer;

    /**
     * Rule constructor.
     *
     * @param \Closure $reducer|null
     */
    public function __construct(\Closure $reducer = null)
    {
        $this->reducer = $reducer;
    }

    /**
     * @param int $type
     * @param int $offset
     * @param array|NodeInterface[]|TokenInterface[] $children
     * @return iterable|NodeInterface[]|NodeInterface
     */
    protected function toAst(array $children, int $offset, int $type): iterable
    {
        if ($this->reducer) {
            return ($this->reducer)($children, $offset, $type);
        }

        return $children;
    }

    /**
     * @param array|NodeInterface[] $children
     * @param iterable|NodeInterface|TokenInterface $result
     * @return array|NodeInterface[]
     */
    protected function merge(array $children, $result): array
    {
        if (\is_array($result)) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            return \array_merge($children, $result);
        }

        $children[] = $result;

        return $children;
    }
}
