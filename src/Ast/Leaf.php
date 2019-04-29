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
use Phplrt\Ast\Dumper\RenderableTrait;

/**
 * Class Leaf
 */
class Leaf implements LeafInterface
{
    use RenderableTrait;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * Leaf constructor.
     *
     * @param TokenInterface $token
     */
    public function __construct(TokenInterface $token)
    {
        $this->token = $token;
    }

    /**
     * @param int $group
     * @return string|null
     */
    public function getValue(int $group = 0): ?string
    {
        return $this->token->getValue($group);
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->token->getOffset();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->token->getName();
    }

    /**
     * @param string $name
     * @return bool
     */
    public function is(string $name): bool
    {
        return $this->token->getName() === $name;
    }
}
