<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Buffer\BufferInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Interface provides full information about execution context.
 *
 * @deprecated since phplrt 3.4 and will be removed in 4.0, please use {@see Context} instead.
 */
interface ContextInterface extends ContextOptionsInterface
{
    /**
     * Returns the source being processed.
     */
    public function getSource(): ReadableInterface;

    /**
     * Returns a lexer's buffer.
     */
    public function getBuffer(): BufferInterface;

    /**
     * Returns the parser's current state identifier.
     *
     * Note: Please note that this value is mutable and may change over time.
     *
     * @return array-key
     */
    public function getState();

    /**
     * Returns the parser's current state rule.
     *
     * Note: Please note that this value is mutable and may change over time.
     */
    public function getRule(): RuleInterface;

    /**
     * Returns the parser's current AST node.
     *
     * If the parser does not contain any nodes of the abstract syntax tree,
     * then the method will return NULL.
     *
     * Note: Please note that this value is mutable and may change over time.
     */
    public function getNode(): ?NodeInterface;

    /**
     * Returns the current parsing token.
     *
     * Note: Please note that this value is mutable and may change over time.
     */
    public function getToken(): TokenInterface;
}
