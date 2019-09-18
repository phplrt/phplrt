<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Parser\Exception;

use Phplrt\Contracts\Ast\NodeInterface;

/**
 * Throws when the error of the parsing of the source code happens.
 */
interface ParserRuntimeExceptionInterface extends ParserExceptionInterface
{
    /**
     * Returns an AST node object during which processing errors occurred.
     *
     * @return NodeInterface
     */
    public function getNode(): NodeInterface;
}
