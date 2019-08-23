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
abstract class Node implements NodeInterface, \JsonSerializable
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
        $this->type       = $type;
        $this->attributes = $attributes;
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id'         => $this->getType(),
            'offset'     => $this->getOffset(),
            'attributes' => $this->getAttributes(),
            'children'   => \iterator_to_array($this->getIterator(), false),
        ];
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
}
