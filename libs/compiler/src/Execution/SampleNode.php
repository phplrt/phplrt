<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Execution;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

final class SampleNode implements NodeInterface, \Stringable
{
    /**
     * @var int
     */
    private int $offset;

    /**
     * @var string|int
     */
    private $state;

    /**
     * @var array<SampleNode|TokenInterface>
     */
    public array $children;

    /**
     * @param int $offset
     * @param string|int $state
     * @param array<SampleNode|TokenInterface> $children
     */
    public function __construct(int $offset, $state, array $children)
    {
        $this->offset = $offset;
        $this->state = $state;
        $this->children = $children;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        yield 'children' => $this->children;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return \implode("\n", $this->render(0));
    }

    /**
     * @param positive-int|0 $depth
     * @return non-empty-array<string>
     */
    public function render(int $depth): array
    {
        $prefix = \str_repeat('    ', $depth);

        $result = [$prefix . '<' . $this->state . ' offset="' . $this->offset . '">'];

        foreach ($this->children as $child) {
            switch (true) {
                case $child instanceof self:
                    $result = [
                        ...\array_values($result),
                        ...\array_values($child->render($depth + 1))
                    ];
                    break;

                case $child instanceof TokenInterface:
                    $result[] = $prefix . '    <' . $child->getName() . ' offset="' . $child->getOffset() . '">' .
                        $child->getValue() . '</' . $child->getName() . '>';
                    break;

                default:
                    $result[] = $prefix . '    <' . (string)$child . ' />';
            }
        }

        $result[] = $prefix . '</' . $this->state . '>';

        return $result;
    }

    /**
     * @return string|int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }
}
