<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast\Dumper;

use Phplrt\Ast\LeafInterface;
use Phplrt\Ast\NodeInterface;
use Phplrt\Ast\RuleInterface;

/**
 * Class HoaDumper
 */
class HoaDumper implements DumperInterface
{
    /**
     * @param NodeInterface|RuleInterface|LeafInterface $node
     * @param int $depth
     * @return array
     */
    private function render(NodeInterface $node, int $depth = 1): array
    {
        $prefix = \str_repeat('>  ', $depth);

        if ($node instanceof LeafInterface) {
            return [
                $prefix . 'token(' . $node->getName() . ', ' . $node->getValue() . ')',
            ];
        }

        $result = [$prefix . $node->getName()];

        if ($node instanceof RuleInterface) {
            foreach ($node->getChildren() as $child) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $result = \array_merge($result, $this->render($child, $depth + 1));
            }
        }

        return $result;
    }

    /**
     * @param mixed|NodeInterface $node
     * @return string
     */
    public function dump($node): string
    {
        return \implode("\n", $this->render($node));
    }
}
