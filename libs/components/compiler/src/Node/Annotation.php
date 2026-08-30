<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node;

/**
 * Says something about the element it is written after, apart from what that
 * element recognizes.
 *
 * What an annotation means is decided by the format that has been read, so the
 * name is kept the way it is spelled and an annotation the compiler knows
 * nothing about is still readable here.
 */
final readonly class Annotation extends Node
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The name the annotation is written under, apart from the "@" opening
         * it.
         *
         * @var non-empty-string
         */
        public string $name,
        /**
         * The values the annotation is written with, in the order they are
         * written.
         *
         * @var list<string>
         */
        public array $arguments = [],
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
