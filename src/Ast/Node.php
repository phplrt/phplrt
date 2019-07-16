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
use Phplrt\Dumper\Dumper;

/**
 * Class Node
 */
abstract class Node implements NodeInterface
{
    /**
     * @var string
     */
    protected $name;

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
        $this->name   = $name;
        $this->offset = $offset;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return Dumper::dump($this);
    }
}
