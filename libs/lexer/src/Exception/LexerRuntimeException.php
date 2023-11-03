<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\RuntimeException;
use Phplrt\Contracts\Lexer\TokenInterface;

abstract class LexerRuntimeException extends RuntimeException implements LexerExceptionInterface
{
    final public function __construct(
        string $message,
        ReadableInterface $src,
        ?TokenInterface $tok,
        \Throwable $prev = null
    ) {
        parent::__construct($message, 0, $prev);

        $this->setSource($src);
        $this->setToken($tok);
    }
}
