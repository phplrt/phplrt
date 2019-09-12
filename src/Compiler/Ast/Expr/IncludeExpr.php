<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Expr;

/**
 * Class IncludeExpr
 * @internal Compiler's grammar AST node class
 */
class IncludeExpr extends Expression
{
    /**
     * @var string
     */
    public $inclusion;

    /**
     * Inclusion constructor.
     *
     * @param string $file
     * @param int $offset
     */
    public function __construct(string $file, int $offset)
    {
        $this->inclusion = \trim($file, '"\'');

        parent::__construct($offset);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return '%include(\'' . $this->inclusion . '\')';
    }
}
