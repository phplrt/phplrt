<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

use Phplrt\Compiler\Node\Node;

/**
 * What a token does to the reading, as it is written in the grammar.
 *
 * An action is recorded the way it is spelled rather than by what it means:
 * whether such an action exists at all is decided while the grammar is being
 * read into a lexer, so that an unknown one is reported by the place it is
 * written at.
 */
final readonly class TokenAction extends Node
{
    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The name of the action, as it is written.
         *
         * @var non-empty-string
         */
        public string $name,
        /**
         * The value the action is given, or {@see null} in case of the action
         * is written with no value at all.
         *
         * @var non-empty-string|null
         */
        public ?string $argument = null,
        int $offset = 0,
        int $length = 0,
    ) {
        parent::__construct(
            offset: $offset,
            length: $length,
        );
    }
}
