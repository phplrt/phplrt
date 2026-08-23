<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An error that occurs after the lexical analysis has been started and
 * indicates a problem in the analyzed source.
 */
interface RuntimeExceptionInterface extends LexerExceptionInterface
{
    /**
     * The source the error occurred in.
     */
    public ReadableInterface $source {
        get;
    }

    /**
     * The token the error occurred on.
     */
    public TokenInterface $token {
        get;
    }
}
