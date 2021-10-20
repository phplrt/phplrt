<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Execution;

use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\ContextInterface;

class AstBuilder implements BuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function build(ContextInterface $context, $result)
    {
        if (! \is_string($context->getState())) {
            return null;
        }

        $token = $context->getToken();
        $children = \is_array($result) ? $result : [$result];

        return new SampleNode($token->getOffset(), $context->getState(), $children);
    }
}
