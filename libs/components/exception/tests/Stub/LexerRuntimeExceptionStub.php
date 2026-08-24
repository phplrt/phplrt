<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Stub;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

final class LexerRuntimeExceptionStub extends \RuntimeException implements RuntimeExceptionInterface
{
    public function __construct(
        public readonly ReadableInterface $source,
        public readonly TokenInterface $token,
        string $message = 'Lexical error',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
