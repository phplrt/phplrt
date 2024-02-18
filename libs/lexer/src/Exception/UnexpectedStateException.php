<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

class UnexpectedStateException extends LexerRuntimeException
{
    public static function fromEmptyStates(ReadableInterface $src, \Throwable $e = null): self
    {
        $message = 'No state defined for the selected multistate lexer';

        return new static($message, $src, null, $e);
    }

    /**
     * @param string|int $state
     */
    public static function fromState($state, ReadableInterface $src, ?TokenInterface $tok, \Throwable $e = null): self
    {
        $message = \sprintf('Unrecognized token state #%s', $state);

        return new static($message, $src, $tok, $e);
    }
}
