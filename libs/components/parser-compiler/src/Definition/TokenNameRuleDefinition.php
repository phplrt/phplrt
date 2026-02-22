<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Definition;

final class TokenNameRuleDefinition extends TerminalRuleDefinition
{
    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $tokenName,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function printValue(): string
    {
        $tokenName = \addcslashes($this->tokenName, '"');

        return \sprintf('<name is "%s">', $tokenName);
    }
}
