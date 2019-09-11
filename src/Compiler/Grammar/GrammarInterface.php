<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Grammar;

use Phplrt\Compiler\Ast\Def\Definition;
use Phplrt\Compiler\Ast\Expr\Expression;
use Phplrt\Contracts\Parser\ParserInterface;

/**
 * Interface GrammarInterface
 */
interface GrammarInterface extends ParserInterface
{
    /**
     * {@inheritDoc}
     * @param \SplFileInfo|string|resource $source
     * @return iterable|Definition[]|Expression[]
     */
    public function parse($source): iterable;
}
