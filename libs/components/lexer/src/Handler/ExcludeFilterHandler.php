<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Handler;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Returns all tokens except the specified ones.
 */
final readonly class ExcludeFilterHandler implements HandlerInterface
{
    /**
     * @var list<non-empty-string>
     */
    private array $excludedTokens;

    /**
     * @param iterable<mixed, non-empty-string> $tokens
     */
    public function __construct(iterable $tokens)
    {
        $this->excludedTokens = \iterator_to_array($tokens, false);
    }

    public function handle(ReadableInterface $source, TokenInterface $token): ?TokenInterface
    {
        if (\in_array($token->name, $this->excludedTokens, true)) {
            return null;
        }

        return $token;
    }
}
