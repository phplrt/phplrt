<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

use Phplrt\Lexer\TokenInterface;

/**
 * Class Leaf
 */
class Leaf extends Node implements LeafInterface
{
    /**
     * @var string[]
     */
    private $value;

    /**
     * Leaf constructor.
     *
     * @param TokenInterface $token
     */
    public function __construct(TokenInterface $token)
    {
        parent::__construct($token->getName(), $token->getOffset());

        $this->value = $token->getGroups();
    }

    /**
     * @param int $group
     * @return string|null
     */
    public function getValue(int $group = 0): ?string
    {
        return $this->value[$group] ?? null;
    }
}
