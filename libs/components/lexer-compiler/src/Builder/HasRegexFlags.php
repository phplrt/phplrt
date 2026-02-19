<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Builder;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;

trait HasRegexFlags
{
    /**
     * @var array<non-empty-string, RegexModifier>
     */
    public private(set) array $flags = [
        RegexModifier::Compiled->value => RegexModifier::Compiled,
        RegexModifier::DotAll->value => RegexModifier::DotAll,
        RegexModifier::Utf8->value => RegexModifier::Utf8,
        RegexModifier::Multiline->value => RegexModifier::Multiline,
    ];

    public function enable(RegexModifier $flag): RegexModifier
    {
        return $this->flags[$flag->value] = $flag;
    }

    public function disable(RegexModifier $flag): RegexModifier
    {
        unset($this->flags[$flag->value]);

        return $flag;
    }

    /**
     * @return $this
     */
    public function removePcreFlagDefinition(RegexModifier $definition): self
    {
        foreach ($this->flags as $index => $flag) {
            if ($flag === $definition) {
                unset($this->flags[$index]);
            }
        }

        return $this;
    }
}
