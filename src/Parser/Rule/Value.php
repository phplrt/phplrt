<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Rule;

use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class Value
 */
class Value extends Rule implements TerminalInterface
{
    /**
     * @var string
     */
    public $value;

    /**
     * Value constructor.
     *
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * @param BufferInterface $buffer
     * @return TokenInterface|null
     */
    public function reduce(BufferInterface $buffer): ?TokenInterface
    {
        $haystack = $buffer->current();

        if ($haystack->getValue() === $this->value) {
            return $haystack;
        }

        return null;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return 'like "' . $this->value . '"';
    }
}
