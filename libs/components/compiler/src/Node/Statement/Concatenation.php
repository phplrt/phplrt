<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes all of the given statements, one after another.
 */
final readonly class Concatenation extends Statement
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
