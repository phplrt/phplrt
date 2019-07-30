<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

use Phplrt\Contracts\Ast\NodeInterface;

/**
 * Class Node
 */
abstract class Node implements NodeInterface
{
    use AttributesTrait;

    /**
     * @var string
     */
    public const ATTR_OFFSET = 'offset';

    /**
     * @var int
     */
    private $type;

    /**
     * Node constructor.
     *
     * @param int $type
     * @param array $attributes
     */
    public function __construct(int $type, array $attributes = [])
    {
        $this->type = $type;
        $this->setAttributes($attributes);
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return (int)$this->getAttribute(self::ATTR_OFFSET, 0);
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \EmptyIterator();
    }
}
