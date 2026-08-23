<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An error that occurs after the syntax analysis has been started and
 * indicates a problem in the analyzed source.
 */
interface RuntimeExceptionInterface extends ParserExceptionInterface
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

    /**
     * The size of the source fragment the error occurred in, in bytes, or
     * {@see null} in case the size is not known.
     *
     * The fragment starts at the offset of the token the error occurred on and
     * MAY be as large as the whole grammar rule the analysis has failed on.
     *
     * @var int<0, max>|null
     */
    public ?int $length {
        get;
    }
}
