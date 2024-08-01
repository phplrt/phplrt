<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer\Value;

use Phplrt\Compiler\Printer\PrintableValueInterface;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;

abstract class RuleInstantiation implements PrintableValueInterface
{
    /**
     * @readonly
     *
     * @psalm-readonly-allow-private-mutation
     */
    protected RuleInterface $rule;

    public function __construct(RuleInterface $rule)
    {
        $this->rule = $rule;
    }

    protected function getArguments(): array
    {
        $rule = $this->rule;

        switch (true) {
            case $rule instanceof Alternation:
            case $rule instanceof Concatenation:
                return [$rule->sequence];
            case $rule instanceof Lexeme:
                return [$rule->token, $rule->keep];
            case $rule instanceof Optional:
                return [$rule->rule];
            case $rule instanceof Repetition:
                return [$rule->rule, $rule->gte, $rule->lte];
            default:
                return [];
        }
    }
}
