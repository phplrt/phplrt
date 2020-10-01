<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
class SampleNode implements NodeInterface
{
    /**
     * @var int
     */
    private int $offset;

    /**
     * @var string
     */
    private string $state;

    /**
     * @var array|SampleNode[]|TokenInterface[]
     */
    public array $children;

    /**
     * SampleNode constructor.
     *
     * @param int $offset
     * @param string $state
     * @param array $children
     */
    public function __construct(int $offset, string $state, array $children)
    {
        $this->offset = $offset;
        $this->state = $state;
        $this->children = $children;
    }

    /**
     * @return \Traversable|SampleNode[][]|TokenInterface[][]
     */
    public function getIterator(): \Traversable
    {
        yield 'children' => $this->children;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return \implode("\n", $this->render(0));
    }

    /**
     * @param int $depth
     * @return array|string[]
     */
    public function render(int $depth): array
    {
        $prefix = \str_repeat('    ', $depth);

        $result[] = $prefix . '<' . $this->state . ' offset="' . $this->offset . '">';

        foreach ($this->children as $child) {
            switch (true) {
                case $child instanceof self:
                    $result = [...$result, ...$child->render($depth + 1)];
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
}
