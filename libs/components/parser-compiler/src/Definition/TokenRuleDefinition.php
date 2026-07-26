<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Definition;

use Phplrt\Compiler\Lexer\Definition\TokenDefinition;

/**
 * Recognizes the given token of the lexer.
 *
 * The token is referred to by its definition, so it does not have to be named.
 */
final class TokenRuleDefinition extends TerminalRuleDefinition
{
    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        public TokenDefinition $token,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function printValue(): string
    {
        return \sprintf('<%s>', $this->token);
    }
}
