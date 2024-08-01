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
     * @param non-empty-string $name
     */
    public function __construct(
        /**
         * @readonly
         */
        private string $name
    ) {}

    public function handle(ReadableInterface $source, TokenInterface $token): ?TokenInterface
    {
        if ($token->getName() === $this->name) {
            return $token;
        }

        return null;
    }
}
