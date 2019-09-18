<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Builder;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Parser\Rule\TerminalInterface;

/**
 * Class Common
 */
class Common implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function build(RuleInterface $rule, TokenInterface $token, $state, $children)
    {
        switch (true) {
            case $rule instanceof TerminalInterface:
                return $rule->isKeep() ? $this->terminal($state, $token) : [];

            case \is_string($state):
                $children = \is_array($children) ? $children : [$children];

                return $this->production($state, $children, $token->getOffset());

            default:
                return null;
        }
    }

    /**
     * @param int|string $state
     * @param TokenInterface $token
     * @return NodeInterface
     */
    protected function terminal($state, TokenInterface $token): NodeInterface
    {
        return new class($state, $token) implements NodeInterface {
            private $state;
            private $token;

            public function __construct($state, TokenInterface $token)
            {
                $this->state = $state;
                $this->token = $token;
            }

            public function getName(): string
            {
                return \is_string($this->state) ? $this->state : $this->token->getName();
            }

            public function getOffset(): int
            {
                return $this->token->getOffset();
            }

            public function getIterator(): \Traversable
            {
                return new \EmptyIterator();
            }
        };
    }

    /**
     * @param string $state
     * @param array $children
     * @param int $offset
     * @return NodeInterface
     */
    protected function production(string $state, array $children, int $offset): NodeInterface
    {
        return new class($state, $children, $offset) implements NodeInterface {
            private $state;
            private $offset;
            public $children;

            public function __construct($state, array $children, int $offset)
            {
                $this->state    = $state;
                $this->children = $children;
                $this->offset   = $offset;
            }

            public function getName(): string
            {
                return $this->state;
            }

            public function getOffset(): int
            {
                return $this->offset;
            }

            public function getIterator(): \Traversable
            {
                return new \ArrayIterator($this->children);
            }
        };
    }
}
