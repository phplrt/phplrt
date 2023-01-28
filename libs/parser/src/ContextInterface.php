<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Interface provides full information about execution context
 */
interface ContextInterface extends ContextOptionsInterface
{
    /**
     * Returns the source being processed.
     *
     * @return ReadableInterface
     */
    public function getSource(): ReadableInterface;

    /**
     * Returns a lexer's buffer.
     *
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface;

    /**
     * Returns the parser's current state identifier.
     *
     * Note: Please note that this value is mutable and may change over time.
     *
     * @return int<0, max>|non-empty-string
     */
    public function getState();

    /**
     * Returns the parser's current state rule.
     *
     * Note: Please note that this value is mutable and may change over time.
     *
     * @return RuleInterface
     */
    public function getRule(): RuleInterface;

    /**
     * Returns the parser's current AST node.
     *
     * If the parser does not contain any nodes of the abstract syntax tree,
     * then the method will return NULL.
     *
     * Note: Please note that this value is mutable and may change over time.
     *
     * @return NodeInterface|null
     */
    public function getNode(): ?NodeInterface;

    /**
     * Returns the current parsing token.
     *
     * Note: Please note that this value is mutable and may change over time.
     *
     * @return TokenInterface
     */
    public function getToken(): TokenInterface;
}
