<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Exception\UnexpectedTokenException;

/**
 * Something the analysis has to say about the source it has read.
 */
final class Diagnostic
{
    /**
     * What is wrong, worded to be shown as it is.
     */
    public string $message {
        get => $this->error->getMessage();
    }

    /**
     * The token it is said about.
     */
    public TokenInterface $token {
        get => $this->error->token;
    }

    /**
     * The identifiers of the tokens that could have been read instead, in no
     * particular order.
     *
     * @var list<int>
     */
    public array $expected {
        get => $this->error->expected;
    }

    public function __construct(
        /**
         * The error the source would be rejected with, ready to be thrown as
         * it is or printed along with the fragment it occurred in.
         */
        public readonly UnexpectedTokenException $error,
    ) {}
}
