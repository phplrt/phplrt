<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes the first of the given statements that matches the input.
 *
 * For example,
 * ```
 * Number() | Name()
 * ```
 */
final readonly class Alternation extends Statement
{
    /**
     * @param non-empty-list<Statement> $statements
     * @param int<0, max> $offset
     */
    public function __construct(
        public array $statements,
        int $offset = 0,
    ) {
        parent::__construct($statements, $offset);
    }
}
