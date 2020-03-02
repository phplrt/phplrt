<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\ContextInterface;

/**
 * Class AstBuilder
 */
class AstBuilder implements BuilderInterface
{
    /**
     * @param ContextInterface $context
     * @param array|iterable|NodeInterface|TokenInterface $result
     * @return mixed
     */
    public function build(ContextInterface $context, $result)
    {
        if (! \is_string($context->getState())) {
            return null;
        }

        $token = $context->getToken();
        $result = \is_array($result) ? $result : [$result];

        return new SampleNode($token->getOffset(), (string)$context->getState(), $result);
    }
}
