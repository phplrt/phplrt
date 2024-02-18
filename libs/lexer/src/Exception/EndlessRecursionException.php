<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

class EndlessRecursionException extends UnexpectedStateException
{
    public static function fromState($state, ReadableInterface $src, ?TokenInterface $tok, \Throwable $e = null): self
    {
        $message = \vsprintf('An unsolvable infinite lexer state transitions was found at %s', [
            $state,
        ]);

        return new static($message, $src, $tok, $e);
    }
}
