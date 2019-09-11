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
 * Class PatternStmt
 * @internal Compiler's grammar AST node class
 */
class PatternStmt extends Statement
{
    /**
     * @var string
     */
    public $pattern;

    /**
     * TokenDefinition constructor.
     *
     * @param string $pattern
     * @param int $offset
     */
    public function __construct(string $pattern, int $offset)
    {
        $this->pattern = $pattern;

        parent::__construct($offset);
    }
}
