<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Visitor\Visitor;
use Phplrt\Compiler\Ast\Def\Definition;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Compiler\Ast\Expr\Expression;
use Phplrt\Compiler\Ast\Expr\IncludeExpr;
use Phplrt\Compiler\Exception\GrammarException;
use Phplrt\Source\Exception\NotAccessibleException;

/**
 * Class IncludesExecutor
 */
class IncludesExecutor extends Visitor
{
    /**
     * @var string
     */
    private const ERROR_NOT_FOUND = '%s: failed to open stream: No such file or directory';

    /**
     * @var string[]
     */
    private const FILE_EXTENSIONS = ['', '.pp2', '.pp'];

    /**
     * @var \Closure
     */
    private $loader;

    /**
     * IncludesExecutor constructor.
     *
     * @param \Closure $loader
     */
    public function __construct(\Closure $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param NodeInterface $node
     * @return mixed|null
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function leave(NodeInterface $node)
    {
        if ($node instanceof IncludeExpr) {
            return $this->lookup($node);
        }

        return $node;
    }

    /**
     * @param IncludeExpr $expr
     * @return array
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    private function lookup(IncludeExpr $expr): array
    {
        $pathname = $expr->getTargetPathname();

        foreach (self::FILE_EXTENSIONS as $ext) {
            if (\is_file($pathname . $ext)) {
                return $this->execute($pathname . $ext);
            }
        }

        $message = \sprintf(self::ERROR_NOT_FOUND, $expr->render());

        throw new GrammarException($message, $expr->file, $expr->offset);
    }

    /**
     * @param string $pathname
     * @return iterable|Definition[]|Expression[]
     */
    private function execute(string $pathname)
    {
        return ($this->loader)($pathname);
    }
}
