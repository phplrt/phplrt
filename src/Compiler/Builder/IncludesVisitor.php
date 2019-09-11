<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Builder;

use Phplrt\Compiler\Ast\Def\Definition;
use Phplrt\Compiler\Ast\Expr\Expression;
use Phplrt\Compiler\Ast\Expr\IncludeExpr;
use Phplrt\Compiler\Builder;
use Phplrt\Compiler\Exception\GrammarException;
use Phplrt\Contracts\Ast\NodeInterface;

/**
 * Class IncludesVisitor
 */
class IncludesVisitor extends Visitor
{
    /**
     * @var string
     */
    private const ERROR_INVALID_SOURCE = 'Can not find "%s" grammar file';

    /**
     * @var string[]
     */
    private const FILE_EXTENSIONS = ['', '.pp2', '.pp'];

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var \SplObjectStorage
     */
    private $stack;

    /**
     * IncludesVisitor constructor.
     *
     * @param \SplFileInfo $from
     * @param Builder $builder
     * @param \SplObjectStorage $stack
     */
    public function __construct(\SplFileInfo $from, Builder $builder, \SplObjectStorage $stack)
    {
        $this->builder = $builder;
        $this->stack   = $stack;

        parent::__construct($from);
    }

    /**
     * @param NodeInterface $node
     * @return mixed|void|null
     */
    public function enter(NodeInterface $node)
    {
        if ($node instanceof IncludeExpr) {
            $this->stack->attach($node, $this->file);
        }
    }

    /**
     * @param NodeInterface $node
     * @return mixed|null
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function leave(NodeInterface $node)
    {
        if ($node instanceof IncludeExpr) {
            $result = $this->lookup($node);

            $this->stack->detach($node);

            return $result;
        }

        return $node;
    }

    /**
     * @param IncludeExpr $expr
     * @return array
     * @throws \ReflectionException
     * @throws \Throwable
     */
    private function lookup(IncludeExpr $expr): array
    {
        $pathname = \dirname($this->file->getPathname()) . '/' . $expr->file;

        foreach (self::FILE_EXTENSIONS as $ext) {
            if (\is_file($pathname . $ext)) {
                return $this->execute($pathname . $ext);
            }
        }

        $exception = new GrammarException(\sprintf(self::ERROR_INVALID_SOURCE, $expr->file));

        throw $this->error($exception, $expr);
    }

    /**
     * @param string $pathname
     * @return iterable|Definition[]|Expression[]
     * @throws \Throwable
     */
    private function execute(string $pathname)
    {
        return $this->builder->analyze(new \SplFileInfo($pathname));
    }
}
