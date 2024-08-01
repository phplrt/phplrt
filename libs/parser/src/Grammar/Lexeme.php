<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * @final marked as final since phplrt 3.4 and will be final since 4.0
 */
class Lexeme extends Terminal
{
    public readonly string|int $token;

    /**
     * @param non-empty-string|int $token
     */
    public function __construct(string|int $token, bool $keep = true)
    {
        parent::__construct($keep);

        $this->token = $token;
    }

    public function reduce(BufferInterface $buffer): ?TokenInterface
    {
        $haystack = $buffer->current();

        if ($haystack->getName() === $this->token) {
            return $haystack;
        }

        return null;
    }
}
