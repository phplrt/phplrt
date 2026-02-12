<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Definition;

final class ValueTokenDefinition extends TokenDefinition
{
    /**
     * @param non-empty-string|null $name
     */
    public function __construct(
        /**
         * @var non-empty-string
         */
        public readonly string $value,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    public function __toString(): string
    {
        $name = $this->fqn ?? '*anonymous*';
        $value = \addcslashes($this->value, '"');

        return \vsprintf('"%s" (%s)', [
            $value,
            $name,
        ]);
    }
}
