<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

class UnexpectedStateException extends LexerRuntimeException
{
    /**
     * @var string
     */
    private const ERROR_UNEXPECTED_STATE = 'Unrecognized token state #%s';

    /**
     * @var string
     */
    private const ERROR_NO_STATES = 'No state defined for the selected multistate lexer';

    public static function fromEmptyStates(ReadableInterface $src, \Throwable $e = null): self
    {
        return new static(self::ERROR_NO_STATES, $src, null, $e);
    }

    /**
     * @param string|int $state
     */
    public static function fromState($state, ReadableInterface $src, ?TokenInterface $tok, \Throwable $e = null): self
    {
        $message = \sprintf(self::ERROR_UNEXPECTED_STATE, $state);

        return new static($message, $src, $tok, $e);
    }
}
