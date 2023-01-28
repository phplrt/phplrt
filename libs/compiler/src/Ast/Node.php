<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * @internal This is an internal class, please do not use it in your application code.
 * @psalm-internal Phplrt\Compiler
 */
abstract class Node implements NodeInterface
{
    /**
     * @var ReadableInterface
     */
    public ReadableInterface $file;

    /**
     * @var int<0, max>
     */
    public int $offset = 0;

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \EmptyIterator();
    }
}
