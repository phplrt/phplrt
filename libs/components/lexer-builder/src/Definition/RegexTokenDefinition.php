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
         * The expression the token is recognized by.
         *
         * @var non-empty-string
         */
        public string $regex,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    /**
     * Updates the expression the token is recognized by and returns itself as
     * the fluent interface.
     *
     * @api
     *
     * @param non-empty-string $regex
     * @return $this
     */
    public function setRegex(string $regex): self
    {
        $this->regex = $regex;

        return $this;
    }

    protected function printValue(): string
    {
        return \sprintf('/%s/', \addcslashes($this->regex, '/'));
    }
}
