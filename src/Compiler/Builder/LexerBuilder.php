<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Builder;

use Phplrt\Compiler\Ast\Def\TokenDef;
use Phplrt\Compiler\Ast\Stmt\PatternStmt;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Lexer;
use Phplrt\Visitor\Visitor;

/**
 * Class LexerBuilder
 */
class LexerBuilder extends Visitor
{
    /**
     * @var array|string[]
     */
    private $lexemes = [];

    /**
     * @var array|string[]
     */
    private $skip = [];

    /**
     * @param NodeInterface $node
     * @return mixed|null
     */
    public function enter(NodeInterface $node)
    {
        if ($node instanceof TokenDef) {
            $this->lexemes[$node->name] = $node->value;

            if (! $node->keep) {
                $this->skip[] = $node->name;
            }
        }

        if ($node instanceof PatternStmt) {
            $lexemes              = \array_reverse($this->lexemes);
            $lexemes[$node->name] = $node->pattern;

            $this->lexemes = \array_reverse($lexemes);
        }

        return parent::enter($node);
    }

    /**
     * @return array|string[]
     */
    public function getTokens(): array
    {
        return $this->lexemes;
    }

    /**
     * @return array|string[]
     */
    public function getSkips(): array
    {
        return $this->skip;
    }

    /**
     * @return LexerInterface
     */
    public function getLexer(): LexerInterface
    {
        return new Lexer($this->lexemes, $this->skip);
    }
}
