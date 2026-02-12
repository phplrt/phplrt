<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Definition;

final class RegexTokenDefinition extends TokenDefinition
{
    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $regex,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    public function __toString(): string
    {
        $name = $this->fqn ?? '*anonymous*';
        $pattern = \addcslashes($this->regex, '/');

        return \vsprintf('/%s/ (%s)', [
            $pattern,
            $name,
        ]);
    }
}
