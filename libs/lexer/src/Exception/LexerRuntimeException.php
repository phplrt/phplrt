<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\RuntimeException;

abstract class LexerRuntimeException extends RuntimeException implements
    LexerExceptionInterface,
    LexerRuntimeExceptionInterface
{
    final public function __construct(
        string $message,
        ReadableInterface $src,
        ?TokenInterface $tok = null,
        ?\Throwable $prev = null
    ) {
        parent::__construct($message, 0, $prev);

        $this->setSource($src);
        $this->setToken($tok);
    }
}
