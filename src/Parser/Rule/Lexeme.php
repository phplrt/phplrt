<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Buffer\BufferInterface;

/**
 * Class Lexeme
 */
class Lexeme extends Terminal
{
    /**
     * @var string
     */
    public $token;

    /**
     * Lexeme constructor.
     *
     * @param string $token
     * @param bool $keep
     */
    public function __construct(string $token, bool $keep = true)
    {
        parent::__construct($keep);

        $this->token = $token;
    }

    /**
     * @param BufferInterface $buffer
     * @return TokenInterface|null
     */
    public function reduce(BufferInterface $buffer): ?TokenInterface
    {
        $haystack = $buffer->current();

        if ($haystack->getName() === $this->token) {
            return $haystack;
        }

        return null;
    }
}
