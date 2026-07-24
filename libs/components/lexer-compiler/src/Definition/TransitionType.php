<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Definition;

enum TransitionType
{
    /**
     * Pushes a new state onto the lexer's state stack.
     */
    case Enter;

    /**
     * Pops the topmost state from the lexer's state stack, returning the
     * lexer to the state it came from.
     */
    case Exit;
}
