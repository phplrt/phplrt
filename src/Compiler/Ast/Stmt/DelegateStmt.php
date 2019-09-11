<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * Class DelegateStmt
 * @internal Compiler's grammar AST node class
 */
class DelegateStmt extends Statement
{
    /**
     * @var string
     */
    public $code;

    /**
     * DelegateStmt constructor.
     *
     * @param string|null $code
     * @param int $offset
     */
    public function __construct(?string $code, int $offset)
    {
        $this->code = $code ?? 'return $children;';

        parent::__construct($offset);
    }
}
