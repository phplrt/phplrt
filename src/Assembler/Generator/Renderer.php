<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Generator;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;

/**
 * Class Renderer
 */
class Renderer extends Standard implements RendererInterface
{
    /**
     * @param iterable|Node[] $ast
     * @param bool $raw
     * @return string
     */
    public function render(iterable $ast, bool $raw = true): string
    {
        return $raw ? $this->prettyPrint($ast) : $this->prettyPrintFile($ast);
    }

    /**
     * Note: Added missing whitespace before "use" statement.
     *
     * @param Expr\Closure $node
     * @return string
     * @throws \Exception
     */
    protected function pExpr_Closure(Expr\Closure $node): string
    {
        return ($node->static ? 'static ' : '')
            . 'function ' . ($node->byRef ? '&' : '')
            . '(' . $this->pCommaSeparated($node->params) . ')'
            . (! empty($node->uses) ? ' use (' . $this->pCommaSeparated($node->uses) . ')' : '')
            . (null !== $node->returnType ? ': ' . $this->p($node->returnType) : '')
            . ' {' . $this->pStmts($node->stmts) . $this->nl . '}';
    }

    /**
     * Note: Forced short declaration array style.
     *
     * @param Expr\Array_ $node
     * @return string
     */
    protected function pExpr_Array(Expr\Array_ $node): string
    {
        return '[' . $this->pMaybeMultiline($node->items, true) . ']';
    }

    /**
     * @param array $nodes
     * @param bool $trailingComma
     * @return string
     */
    private function pMaybeMultiline(array $nodes, $trailingComma = false): string
    {
        if (! $this->hasNodeWithComments($nodes)) {
            return $this->pCommaSeparated($nodes);
        }

        return $this->pCommaSeparatedMultiline($nodes, $trailingComma) . $this->nl;
    }

    /**
     * @param Node[] $nodes
     * @return bool
     */
    private function hasNodeWithComments(array $nodes): bool
    {
        foreach ($nodes as $node) {
            if ($node && $node->getComments()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Note: Remove whitespace before double colon ":" of type hint.
     *
     * @param Stmt\ClassMethod $node
     * @return string
     * @throws \Exception
     */
    protected function pStmt_ClassMethod(Stmt\ClassMethod $node): string
    {
        return $this->nl
            . $this->pModifiers($node->flags)
            . 'function ' . ($node->byRef ? '&' : '') . $node->name
            . '(' . $this->pCommaSeparated($node->params) . ')'
            . (null !== $node->returnType ? ': ' . $this->p($node->returnType) : '')
            . (null !== $node->stmts
                ? $this->nl . '{' . $this->pStmts($node->stmts) . $this->nl . '}'
                : ';');
    }
}
