<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\StackTrace\Record;

use Phplrt\Contracts\Ast\NodeInterface;

/**
 * Class NodeRecord
 */
class NodeRecord extends Record
{
    /**
     * @var NodeInterface
     */
    public $node;

    /**
     * NodeRecord constructor.
     *
     * @param string $pathname
     * @param NodeInterface $node
     */
    public function __construct(string $pathname, NodeInterface $node)
    {
        $this->node = $node;

        parent::__construct($pathname, $node->getOffset());
    }
}
