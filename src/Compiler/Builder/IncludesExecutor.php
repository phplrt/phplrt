<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Builder;

use Phplrt\Visitor\Visitor;
use Phplrt\Compiler\Ast\Node;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Compiler\Ast\Def\Definition;
use Phplrt\Compiler\Ast\Expr\Expression;
use Phplrt\Compiler\Ast\Expr\IncludeExpr;
use Phplrt\Parser\Exception\ParserRuntimeException;

/**
 * Class IncludesExecutor
 */
class IncludesExecutor extends Visitor
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
     * @var \Closure
     */
    private $loader;

    /**
     * @var string
     */
    private $pathname;

    /**
     * IncludesVisitor constructor.
     *
     * @param string $pathname
     * @param \Closure $loader
     */
    public function __construct(string $pathname, \Closure $loader)
    {
        $this->pathname = $pathname;
        $this->loader = $loader;
    }

    /**
     * @param NodeInterface $node
     * @return mixed|null
     */
    public function enter(NodeInterface $node)
    {
        if ($node instanceof Node && $node->file === null) {
            $node->file = $this->pathname;
        }

        return parent::enter($node);
    }

    /**
     * @param NodeInterface $node
     * @return mixed|null
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
     * @throws ParserRuntimeException
     */
    private function lookup(IncludeExpr $expr): array
    {
        $pathname = $this->getPathname($expr->inclusion);

        foreach (self::FILE_EXTENSIONS as $ext) {
            if (\is_file($pathname . $ext)) {
                return $this->execute($pathname . $ext);
            }
        }

        throw new ParserRuntimeException(\sprintf(self::ERROR_INVALID_SOURCE, $expr->inclusion), $expr);
    }

    /**
     * @param string $file
     * @return string
     */
    private function getPathname(string $file): string
    {
        return $this->getDirname() . \DIRECTORY_SEPARATOR . $file;
    }

    /**
     * @return string
     */
    private function getDirname(): string
    {
        return \dirname($this->pathname);
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
