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
 * @internal PatternStmt is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\compiler
 */
class PatternStmt extends Statement
{
    /**
     * @var array<string, int>
     */
    private static $identifiers = [];

    /**
     * @var string
     */
    public string $pattern;

    /**
     * @var int
     */
    public int $name;

    /**
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
        return self::$identifiers[$this->pattern] ??= \count(self::$identifiers);
    }
}
