<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Expr;

use Phplrt\Contracts\Source\FileInterface;

/**
 * Class IncludeExpr
 *
 * @internal Compiler's grammar AST node class
 */
class IncludeExpr extends Expression
{
    /**
     * @var string
     */
    private $target;

    /**
     * Inclusion constructor.
     *
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->target = \trim($file, '"\'');
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return string
     */
    public function getTargetPathname(): string
    {
        if (! $this->file instanceof FileInterface) {
            return $this->target;
        }

        $directory = \dirname($this->file->getPathname());

        return \str_replace('\\', '/', $directory . '/' . \trim($this->target, '/'));
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return '%include(\'' . $this->getTargetPathname() . '\')';
    }
}
