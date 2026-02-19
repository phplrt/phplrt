<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

final readonly class Alternation implements RuleInterface
{
    public function __construct(
        /**
         * @var non-empty-list<int>
         */
        public array $ruleIds,
    ) {}
}
