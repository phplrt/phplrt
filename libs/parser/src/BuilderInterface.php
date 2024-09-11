<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

interface BuilderInterface
{
    /**
     * Note: Native type hints will be added in phplrt 4.0, as adding them
     *       clearly breaks backward compatibility with inheritance.
     *
     * @param NodeInterface|TokenInterface|iterable<NodeInterface|TokenInterface> $result
     */
    public function build(Context $context, /* mixed */ $result)/*: mixed*/;
}
