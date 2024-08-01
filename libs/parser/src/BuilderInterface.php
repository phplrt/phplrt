<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

interface BuilderInterface
{
    /**
     * @param NodeInterface|TokenInterface|iterable<NodeInterface|TokenInterface> $result
     */
    public function build(Context $context, mixed $result);
}
