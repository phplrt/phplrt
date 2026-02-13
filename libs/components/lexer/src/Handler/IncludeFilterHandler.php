<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Handler;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Returns only the specified tokens and excludes the rest
 */
final readonly class IncludeFilterHandler implements HandlerInterface
{
    /**
     * @var list<non-empty-string>
     */
    private array $allowedTokens;

    /**
     * @param iterable<mixed, non-empty-string> $tokens
     */
    public function __construct(iterable $tokens)
    {
        $this->allowedTokens = \iterator_to_array($tokens, false);
    }

    public function handle(ReadableInterface $source, TokenInterface $token): ?TokenInterface
    {
        if (\in_array($token->name, $this->allowedTokens, true)) {
            return $token;
        }

        return null;
    }
}
