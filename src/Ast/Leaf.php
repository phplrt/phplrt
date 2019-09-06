<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class Leaf
 */
class Leaf extends Node
{
    /**
     * @var string
     */
    public $value;

    /**
     * Leaf constructor.
     *
     * @param TokenInterface $token
     */
    public function __construct(TokenInterface $token)
    {
        $this->value  = $token->getValue();
        $this->offset = $token->getOffset();
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \EmptyIterator();
    }
}
