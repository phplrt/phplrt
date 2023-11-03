<?php

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\ContextInterface;

class AstBuilder implements BuilderInterface
{
    public function build(ContextInterface $context, $result): ?SampleNode
    {
        if (!\is_string($context->getState())) {
            return null;
        }

        $token = $context->getToken();

        /** @var array<SampleNode> $result */
        $result = \is_array($result) ? $result : [$result];

        return new SampleNode($token->getOffset(), (string)$context->getState(), $result);
    }
}
