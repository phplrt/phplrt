<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Stub;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

final class ParserRuntimeExceptionStub extends \RuntimeException implements RuntimeExceptionInterface
{
    public function __construct(
        public readonly ReadableInterface $source,
        public readonly TokenInterface $token,
        public readonly ?int $length = null,
        string $message = 'Syntax error',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
