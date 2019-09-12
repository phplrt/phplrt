<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\StackTrace;

use Phplrt\Contracts\Ast\NodeInterface;

/**
 * Interface TraceableNodeInterface
 */
interface TraceableNodeInterface extends NodeInterface
{
    /**
     * @return string
     */
    public function getFile(): string;
}
