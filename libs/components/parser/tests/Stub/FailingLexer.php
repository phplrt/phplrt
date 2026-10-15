<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Stub;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * @readonly
 */
final class FailingLexer implements LexerInterface
{
    public function __construct(
        private readonly \Throwable $error,
    ) {}

    public function lex(ReadableInterface $source, int $offset = 0): iterable
    {
        throw $this->error;
    }
}
