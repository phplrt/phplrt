<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * An exception that occurs after starting the lexical analysis and indicates
 * problems in the analyzed source.
 */
interface RuntimeExceptionInterface extends LexerExceptionInterface
{
    /**
     * Gets the token on which the error occurred.
     */
    public TokenInterface $token {
        get;
    }
}
