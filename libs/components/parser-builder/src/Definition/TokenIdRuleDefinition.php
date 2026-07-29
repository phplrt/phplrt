<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

final class TokenIdRuleDefinition extends TerminalRuleDefinition
{
    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        public int $tokenId,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function printValue(): string
    {
        return \sprintf('<id is %d>', $this->tokenId);
    }
}
