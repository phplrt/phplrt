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

    /**
     * The size of the source fragment the parser error occurred in, in bytes.
     *
     * A parser fails on a token and may span as far as the rule it has failed
     * on, which is what this tells apart from the token itself. If the size is
     * not specified ({@see null}), that of the token ({@see TokenInterface::$size})
     * can be used instead.
     *
     * @var int<0, max>|null
     */
    public ?int $length {
        get;
    }
}
