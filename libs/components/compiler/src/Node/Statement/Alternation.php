<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes the first of the given statements that matches the input.
 */
final readonly class Alternation extends Statement
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * @var non-empty-list<Statement>
         */
        public array $statements,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            children: $statements,
            offset: $offset,
            length: $length,
        );
    }
}
