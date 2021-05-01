<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Contracts\Buffer\BufferInterface;

class Lexeme extends Terminal
{
    /**
     * @var string|int
     */
    public $token;

    /**
     * Lexeme constructor.
     *
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
    public function reduce(BufferInterface $buffer): ?\Phplrt\Contracts\Lexer\TokenInterface
    {
        $haystack = $buffer->current();

        if ($haystack->getName() === $this->token) {
            return $haystack;
        }

        return null;
    }
}
