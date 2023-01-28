<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Visitor\Visitor;
use Phplrt\Compiler\Ast\Def\RuleDef;
use Phplrt\Compiler\Ast\Def\TokenDef;
use Phplrt\Contracts\Ast\NodeInterface;

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

    /**
     * {@inheritDoc}
     */
    public function enter(NodeInterface $node): void
    {
        if ($node instanceof RuleDef) {
            $this->rules[$node->name] = $node->keep;
        }

        if ($node instanceof TokenDef) {
            $this->tokens[$node->name] = $node->keep;
        }
    }

    /**
     * @param string $name
     * @return bool|null
     */
    public function lexeme(string $name): ?bool
    {
        return $this->tokens[$name] ?? null;
    }

    /**
     * @param string $name
     * @return bool|null
     */
    public function rule(string $name): ?bool
    {
        return $this->rules[$name] ?? null;
    }
}
