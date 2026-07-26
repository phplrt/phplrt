<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Definition;

/**
 * @phpstan-sealed TokenIdRuleDefinition|TokenNameRuleDefinition|TokenRuleDefinition
 */
abstract class TerminalRuleDefinition extends RuleDefinition
{
    /**
     * Contains {@see true} in case of the token should be kept in the syntax
     * tree (a name, a literal), or {@see false} in case of it is only consumed
     * (punctuation, such as a comma or a bracket)
     */
    public private(set) bool $isKept = true;

    /**
     * @api
     *
     * @return $this
     */
    public function keep(): self
    {
        return $this->setKept();
    }

    /**
     * @api
     *
     * @return $this
     */
    public function skip(): self
    {
        return $this->setKept(false);
    }

    /**
     * @api
     *
     * @return $this
     */
    public function setKept(bool $kept = true): self
    {
        $this->isKept = $kept;

        return $this;
    }
}
