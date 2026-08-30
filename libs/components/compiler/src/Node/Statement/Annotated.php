<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Statement;

use Phplrt\Compiler\Node\Annotation;

/**
 * Recognizes what the statement it is written after recognizes, and says
 * something about it apart from that.
 */
final readonly class Annotated extends Statement
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * What is recognized.
         */
        public Statement $statement,
        /**
         * What is said about the statement, in the order it is written.
         *
         * @var non-empty-list<Annotation>
         */
        public array $annotations,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            children: [$statement],
            offset: $offset,
            length: $length,
        );
    }
}
