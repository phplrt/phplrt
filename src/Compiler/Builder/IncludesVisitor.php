<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Builder;

use Phplrt\Compiler\Builder;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Compiler\Ast\Def\Definition;
use Phplrt\Compiler\Ast\Expr\Expression;
use Phplrt\Compiler\Ast\Expr\IncludeExpr;
use Phplrt\Compiler\Exception\GrammarException;

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
     * IncludesVisitor constructor.
     *
     * @param \SplFileInfo $from
     * @param Builder $builder
     */
    public function __construct(\SplFileInfo $from, Builder $builder)
    {
        $this->builder = $builder;

        parent::__construct($from);
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
            return $this->lookup($node);
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
