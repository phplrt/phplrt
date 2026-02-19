<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

final readonly class Optional implements RuleInterface
{
    public function __construct(
        public int $ruleId,
    ) {}
}
