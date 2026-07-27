<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Definition;

enum TransitionType
{
    /**
     * Hands the reading over to a lexer of its own.
     */
    case Enter;

    /**
     * Ends the reading, giving the control back to the lexer that called it.
     */
    case Exit;
}
