<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes another rule of the parser.
 */
final readonly class RuleReference extends Statement
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The name of the rule to recognize, as it is written.
         *
         * @var non-empty-string
         */
        public string $name,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
