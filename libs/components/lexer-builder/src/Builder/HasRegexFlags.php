<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Builder;

use Phplrt\Compiler\Lexer\Definition\RegexModifier;

/**
 * @internal this is an internal library trait, please do not use it in your code
 * @psalm-internal Phplrt\Compiler\Lexer
 */
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
}
