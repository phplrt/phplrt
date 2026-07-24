<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * An exception that occurs after starting the parsing and indicates
 * problems in the analyzed source.
 */
interface RuntimeExceptionInterface extends ParserExceptionInterface
{
    /**
     * Gets the token on which the error occurred.
     */
    public TokenInterface $token {
        get;
    }
}
