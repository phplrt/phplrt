<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * An exception that occurs after starting the parsing and indicates
 * problems in the analyzed source.
 */
interface ParserRuntimeExceptionInterface extends ParserExceptionInterface
{
    /**
     * Returns the token on which the error occurred.
     */
    public function getToken(): TokenInterface;
}
