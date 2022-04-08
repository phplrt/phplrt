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
 *
 * @internal Compiler's grammar AST node class
 */
class PatternStmt extends Statement
{
    /**
     * @var array
     */
    private static $identifiers = [];

    /**
     * @var string
     */
    public $pattern;

    /**
     * @var int
     */
    public $name;

    /**
     * TokenDefinition constructor.
     *
     * @param string $pattern
     */
    public function __construct(string $pattern)
    {
        $this->pattern = \str_replace('\"', '"', $pattern);
        $this->name = $this->getId();
    }

    /**
     * @return int
     */
    private function getId(): int
    {
        return self::$identifiers[$this->pattern] ?? self::$identifiers[$this->pattern] = \count(self::$identifiers);
    }
}
