<?php

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\LexerInterface;

final readonly class LexerCreateInfo
{
    public function __construct(
        /**
         * Generated a PCRE2-compatible regex pattern
         *
         * For example,
         * ```php
         * pattern: '/\\G(?|(?:(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)")(*MARK:0))|(?:(?:.+?)(*MARK:1)))/Ssum',
         * ```
         *
         * @var non-empty-string
         */
        public string $pattern,
        /**
         * A map of token ID and its channels.
         *
         * The list contains the token ID in the array's key and the
         * channel name in the array's value. All reserved channels will be
         * converted to built-in ({@see Channel}), all others to the
         * {@see CustomChannel} instance
         *
         * For example,
         * ```php
         * [
         *     0 => 'hidden',
         *     1 => 'unknown',
         * ]
         * ```
         *
         * @var array<int, non-empty-string>
         */
        public array $channels = [],
        /**
         * A map of token ID and its original names.
         *
         * @var array<int, non-empty-string>
         */
        public array $names = [],
        /**
         * A map of token ID and its transition states.
         *
         * When the specified token is reached in the current state, an
         * unconditional transition to another state with the specified
         * name will be performed.
         *
         * For example,
         * ```php
         * [
         *     0 => 'next-state',
         *     42 => 'other-state',
         * ]
         * ```
         *
         * @var array<int, non-empty-string>
         */
        public array $transitions = [],
        /**
         * Name of the state and its configuration DTOs.
         *
         * The state name is used as the array key; value contains configuration
         * DTO of the state.
         *
         * For example,
         * ```php
         * [
         *      'injected_language' => new LexerStateCreateInfo(...),
         *      'other_language' => new LexerStateCreateInfo(...),
         * ]
         * ```
         *
         * @var array<non-empty-string, LexerInterface>
         */
        public array $states = [],
    ) {}
}
