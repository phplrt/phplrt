<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An exception that occurs after starting the parsing and indicates
 * problems in the analyzed source.
 */
interface ParserRuntimeExceptionInterface extends ParserExceptionInterface
{
    /**
     * Returns the source object in which the error occurred.
     */
    public function getSource(): ReadableInterface;

    /**
     * Returns the token on which the error occurred.
     */
    public function getToken(): TokenInterface;
}
