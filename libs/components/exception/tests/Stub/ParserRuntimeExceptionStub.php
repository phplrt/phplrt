<?php

declare(strict_types=1);

namespace Phplrt\Exception\Tests\Stub;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * A syntax error that has occurred on a token of a source.
 */
final class ParserRuntimeExceptionStub extends \RuntimeException implements RuntimeExceptionInterface
{
    public function __construct(
        public readonly ReadableInterface $source,
        public readonly TokenInterface $token,
        /**
         * @var int<0, max>|null
         */
        public readonly ?int $length = null,
        string $message = 'Syntax error',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
