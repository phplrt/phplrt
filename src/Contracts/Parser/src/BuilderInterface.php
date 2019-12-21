<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Interface BuilderInterface
 */
interface BuilderInterface
{
    /**
     * @param ContextInterface $context
     * @param NodeInterface|TokenInterface|array|iterable $result
     * @return mixed|null
     */
    public function build(ContextInterface $context, $result);
}
