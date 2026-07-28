<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes all of the given statements, one after another.
 *
 * For example,
 * ```
 * Number() ::T_PLUS:: Number()
 * ```
 */
final readonly class Concatenation extends Statement
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
