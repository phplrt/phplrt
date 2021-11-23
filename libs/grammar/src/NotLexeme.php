<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Grammar;

use Phplrt\Contracts\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer;

class NotLexeme extends Terminal
{
    /**
     * @param non-empty-string|int $token
     * @param bool $keep
     */
    public function __construct(
        public readonly string|int $token,
        bool $keep = true
    ) {
        parent::__construct($keep);
    }

    /**
     * {@inheritDoc}
     */
    public function reduce(BufferInterface $buffer): ?Lexer\TokenInterface
    {
        $haystack = $buffer->current();

        if ($haystack->getName() !== $this->token) {
            return $haystack;
        }

        return null;
    }
}
