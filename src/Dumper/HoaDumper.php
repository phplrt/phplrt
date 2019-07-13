<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Dumper;

use Phplrt\Contracts\Ast\LeafInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Ast\RuleInterface;
use Phplrt\Contracts\Dumper\DumperInterface;

/**
 * Class HoaDumper
 */
class HoaDumper implements DumperInterface
{
    /**
     * @var string
     */
    private const LINE_DELIMITER = \PHP_EOL;

    /**
     * @var string
     */
    private const TOKEN_TEMPLATE = 'token(%s, %s)';

    /**
     * @var string
     */
    private const TOKEN_PREFIX = '>  ';

    /**
     * @param NodeInterface|RuleInterface|LeafInterface $node
     * @param int $depth
     * @return array
     */
    private function render(NodeInterface $node, int $depth = 1): array
    {
        $prefix = \str_repeat(self::TOKEN_PREFIX, $depth);

        if ($node instanceof LeafInterface) {
            return [$prefix . \sprintf(self::TOKEN_TEMPLATE, $node->getName(), $node->getValue())];
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
        return \implode(self::LINE_DELIMITER, $this->render($node));
    }
}
