<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Config;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Handler that returns the token "as is".
 */
final class PassthroughWhenTokenHandler implements HandlerInterface
{
    /**
     * @var non-empty-string
     *
     * @readonly
     */
    private string $name;

    /**
     * @param non-empty-string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function handle(ReadableInterface $source, TokenInterface $token): ?TokenInterface
    {
        if ($token->getName() === $this->name) {
            return $token;
        }

        return null;
    }
}
