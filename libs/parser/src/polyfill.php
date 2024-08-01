<?php

namespace Phplrt\Grammar {
    if (
        !\class_exists(Alternation::class, false)
        && \class_exists(\Phplrt\Parser\Grammar\Alternation::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\Alternation::class, Alternation::class);
    }

    if (
        !\class_exists(Builder::class, false)
        && \class_exists(\Phplrt\Parser\Grammar\Builder::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\Builder::class, Builder::class);
    }

    if (
        !\class_exists(Concatenation::class, false)
        && \class_exists(\Phplrt\Parser\Grammar\Concatenation::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\Concatenation::class, Concatenation::class);
    }

    if (
        !\class_exists(Lexeme::class, false)
        && class_exists(\Phplrt\Parser\Grammar\Lexeme::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\Lexeme::class, Lexeme::class);
    }

    if (
        !\class_exists(Optional::class, false)
        && \class_exists(\Phplrt\Parser\Grammar\Optional::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\Optional::class, Optional::class);
    }

    if (
        !\class_exists(Production::class, false)
        && \class_exists(\Phplrt\Parser\Grammar\Production::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\Production::class, Production::class);
    }

    if (
        !\class_exists(Repetition::class, false)
        && \class_exists(\Phplrt\Parser\Grammar\Repetition::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\Repetition::class, Repetition::class);
    }

    if (
        !\class_exists(Rule::class, false)
        && \class_exists(\Phplrt\Parser\Grammar\Rule::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\Rule::class, Rule::class);
    }

    if (
        !\class_exists(Terminal::class, false)
        && \class_exists(\Phplrt\Parser\Grammar\Terminal::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\Terminal::class, Terminal::class);
    }
}

namespace Phplrt\Contracts\Grammar {
    if (
        !\interface_exists(ProductionInterface::class, false)
        && \interface_exists(\Phplrt\Parser\Grammar\ProductionInterface::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\ProductionInterface::class, ProductionInterface::class);
    }

    if (
        !\interface_exists(RuleInterface::class, false)
        && \interface_exists(\Phplrt\Parser\Grammar\RuleInterface::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\RuleInterface::class, RuleInterface::class);
    }

    if (
        !\interface_exists(TerminalInterface::class, false)
        && \interface_exists(\Phplrt\Parser\Grammar\TerminalInterface::class)
    ) {
        \class_alias(\Phplrt\Parser\Grammar\TerminalInterface::class, TerminalInterface::class);
    }
}
