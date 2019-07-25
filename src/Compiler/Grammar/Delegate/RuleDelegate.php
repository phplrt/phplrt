<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Grammar\Delegate;

use Phplrt\Ast\Node;
use Phplrt\Compiler\Grammar\LookaheadIterator;
use Phplrt\Contracts\Ast\LeafInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\Token\Token;

/**
 * Class RuleDelegate
 */
class RuleDelegate extends Node
{
    /**
     * @return iterable|TokenInterface[]|LookaheadIterator
     */
    public function getInnerTokens(): iterable
    {
        return new LookaheadIterator((function () {
            yield from $this->getTokens($this->first('RuleProduction'));
            yield new EndOfInput(0);
        })->call($this));
    }

    /**
     * @param NodeInterface|NodeInterface $rule
     * @return \Traversable
     */
    private function getTokens(NodeInterface $rule): \Traversable
    {
        /** @var LeafInterface $child */
        foreach ($rule->getChildren() as $child) {
            if ($child instanceof NodeInterface) {
                yield from $this->getTokens($child);
            } else {
                yield new Token($child->getName(), $child->getValue(), $child->getOffset());
            }
        }
    }

    /**
     * @param string $name
     * @return LeafInterface|NodeInterface|NodeInterface|null
     */
    private function first(string $name)
    {
        foreach ($this->getChildren() as $child) {
            if ($child->getName() === $name) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getRuleName(): string
    {
        if ($name = $this->first('RuleName')) {
            return $name->getChild(0)->getValue();
        }

        return $this->getName();
    }

    /**
     * @return bool
     */
    public function isKept(): bool
    {
        return $this->first('ShouldKeep') !== null;
    }

    /**
     * @return null|string
     */
    public function getDelegate(): ?string
    {
        $delegate = $this->first('RuleDelegate');

        if ($delegate instanceof NodeInterface) {
            return $delegate->getChild(0)->getValue();
        }

        return null;
    }
}
