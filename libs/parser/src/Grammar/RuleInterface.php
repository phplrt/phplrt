<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * The base interface of all parser rules.
 */
interface RuleInterface
{
    /**
     * @param array<array-key, RuleInterface> $rules
     *
     * @return iterable<TerminalInterface>
     */
    public function getTerminals(array $rules): iterable;
}
