<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Runtime;

use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\Context;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Compiler
 */
class PrintableNodeBuilder implements BuilderInterface
{
    public function build(Context $context, $result): ?PrintableNode
    {
        if (!\is_string($context->getState())) {
            return null;
        }

        $token = $context->getToken();

        /** @var array<PrintableNode> $result */
        $result = \is_array($result) ? $result : [$result];

        return new PrintableNode($token->getOffset(), $context->getState(), $result);
    }
}
