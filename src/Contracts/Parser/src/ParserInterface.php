<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

/**
 * An interface that implements methods for parsing source code.
 */
interface ParserInterface
{
    /**
     * Parses sources into an abstract source tree (AST) or list of AST nodes.
     *
     * @param string|resource $source
     * @return iterable|NodeInterface[]|NodeInterface
     *
     * @throws ParserExceptionInterface
     * @throws RuntimeExceptionInterface
     */
    public function parse($source): iterable;
}
