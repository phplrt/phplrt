<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer;

class Lexeme extends Terminal
{
    /**
     * @var string|int
     */
    public $token;

    /**
     * @param string|int $token
     * @param bool $keep
     */
    public function __construct($token, bool $keep = true)
    {
        parent::__construct($keep);

        $this->token = $token;
    }

    /**
     * {@inheritDoc}
     */
    public function reduce(BufferInterface $buffer): ?Lexer\TokenInterface
    {
        $haystack = $buffer->current();

        if ($haystack->getName() === $this->token) {
            return $haystack;
        }

        return null;
    }
}
