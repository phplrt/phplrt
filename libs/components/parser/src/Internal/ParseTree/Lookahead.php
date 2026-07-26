<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\ParseTree;

final readonly class Lookahead
{
    public function __construct(
        /**
         * The identifiers of the tokens a rule may start with, indexed by the rule
         * identifiers.
         *
         * @var array<int, array<int, true>>
         */
        public array $first = [],
        /**
         * The rules that may be recognized without consuming a token, indexed by
         * the rule identifiers.
         *
         * @var array<int, bool>
         */
        public array $nullable = [],
    ) {}
}
