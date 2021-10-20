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
 * @internal IncludeExpr is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\compiler
 */
class IncludeExpr extends Expression
{
    /**
     * @var string
     */
    private string $target;

    /**
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
