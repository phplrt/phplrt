<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Interface denoting a leaf (that is a terminal) rule.
 */
interface TerminalInterface extends RuleInterface
{
    /**
     * Returns a matched token if the current buffer state is correctly
     * processed. Otherwise, if the rule does not match the required one,
     * it returns null.
     */
    public function reduce(BufferInterface $buffer): ?TokenInterface;

    public function isKeep(): bool;
}
