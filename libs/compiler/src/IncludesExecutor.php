<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Ast\Node;
use Phplrt\Visitor\Visitor;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Compiler\Ast\Expr\IncludeExpr;
use Phplrt\Compiler\Exception\GrammarException;
use Phplrt\Source\Exception\NotAccessibleException;

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
     * @var \Closure(non-empty-string):iterable<Node>
     */
    private \Closure $loader;

    /**
     * @param \Closure(non-empty-string):iterable<Node> $loader
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
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
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
     * @psalm-taint-sink file $pathname
     * @param non-empty-string $pathname
     * @return iterable<Node>
     */
    private function execute(string $pathname): iterable
    {
        return ($this->loader)($pathname);
    }
}
