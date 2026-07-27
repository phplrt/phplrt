<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An exception that occurs after starting the parsing and indicates
 * problems in the analyzed source.
 */
interface RuntimeExceptionInterface extends ParserExceptionInterface
{
    /**
     * Gets the source object in which the error occurred.
     */
    public ReadableInterface $source {
        get;
    }

    /**
     * Gets the token on which the error occurred.
     */
    public TokenInterface $token {
        get;
    }
}
