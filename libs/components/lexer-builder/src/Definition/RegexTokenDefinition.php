<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Definition;

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

    protected function printValue(): string
    {
        return \sprintf('/%s/', \addcslashes($this->regex, '/'));
    }
}
