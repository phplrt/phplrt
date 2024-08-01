<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Context;

use Phplrt\Compiler\Ast\Def\RuleDef;
use Phplrt\Compiler\Ast\Def\TokenDef;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Visitor\Visitor;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Compiler
 */
class IdCollection extends Visitor
{
    /**
     * @var array<bool>
     */
    private array $rules = [];

    /**
     * @var array<bool>
     */
    private array $tokens = [];

    public function enter(NodeInterface $node): void
    {
        if ($node instanceof RuleDef) {
            $this->rules[$node->name] = $node->keep;
        }

        if ($node instanceof TokenDef) {
            $this->tokens[$node->name] = $node->keep;
        }
    }

    public function lexeme(string $name): ?bool
    {
        return $this->tokens[$name] ?? null;
    }

    public function rule(string $name): ?bool
    {
        return $this->rules[$name] ?? null;
    }
}
