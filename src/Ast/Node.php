<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

use Phplrt\Ast\Dumper\RenderableTrait;

/**
 * Class Node
 */
abstract class Node implements NodeInterface
{
    use NameTrait;
    use RenderableTrait;

    /**
     * @var int
     */
    protected $offset;

    /**
     * Node constructor.
     *
     * @param string $name
     * @param int $offset
     */
    public function __construct(string $name, int $offset = 0)
    {
        $this->name = $name;
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }
}
