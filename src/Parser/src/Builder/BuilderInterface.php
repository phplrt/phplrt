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

/**
 * Interface BuilderInterface
 */
interface BuilderInterface
{
    /**
     * @param RuleInterface $rule
     * @param TokenInterface $token
     * @param int|string $state
     * @param NodeInterface|TokenInterface|array|iterable $children
     * @return mixed|null
     */
    public function build(RuleInterface $rule, TokenInterface $token, $state, $children);
}
