<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

/**
 * Recognizes another rule of the parser.
 *
 * For example,
 * ```
 * Number()
 * ```
 */
final readonly class RuleReference extends Statement
{
    /**
     * @param non-empty-string $name
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name of the rule to recognize, as it is written.
         */
        public string $name,
        int $offset = 0,
    ) {
        parent::__construct(offset: $offset);
    }
}
