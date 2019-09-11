<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Builder;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Position\Position;
use Phplrt\Visitor\Visitor as BaseVisitor;

/**
 * Class Visitor
 */
abstract class Visitor extends BaseVisitor
{
    /**
     * @var \SplFileInfo
     */
    protected $file;

    /**
     * Visitor constructor.
     *
     * @param \SplFileInfo $file
     */
    public function __construct(\SplFileInfo $file)
    {
        $this->file = $file;
    }

    /**
     * @param \Throwable $e
     * @param NodeInterface $node
     * @return \Throwable
     * @throws \ReflectionException
     */
    protected function error(\Throwable $e, NodeInterface $node): \Throwable
    {
        $position = Position::fromOffset(\fopen($this->file->getPathname(), 'rb+'), $node->getOffset());

        return $position->inject($e, $this->file->getPathname());
    }
}
