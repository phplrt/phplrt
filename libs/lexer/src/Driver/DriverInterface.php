<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\UnknownToken;

/**
 * @deprecated since phplrt 3.6 and will be removed in 4.0.
 *
 * @internal This is an internal library interface, please do not use it in your code.
 * @psalm-internal Phplrt\Lexer
 */
interface DriverInterface
{
    /**
     * @var non-empty-string
     */
    public const UNKNOWN_TOKEN_NAME = UnknownToken::DEFAULT_TOKEN_NAME;

    /**
     * @param array<array-key, non-empty-string> $tokens
     * @param int<0, max> $offset
     *
     * @return iterable<TokenInterface>
     */
    public function run(array $tokens, ReadableInterface $source, int $offset = 0): iterable;

    public function reset(): void;
}
