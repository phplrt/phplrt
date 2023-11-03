<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

class Lexeme extends Terminal
{
    /**
     * @var non-empty-string|int
     * @readonly
     */
    public $token;

    /**
     * @param non-empty-string|int $token
     */
    public function __construct($token, bool $keep = true)
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
