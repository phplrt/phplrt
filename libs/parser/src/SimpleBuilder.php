<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Parser\Context\TreeBuilder;

/**
 * @deprecated since phplrt 3.4 and will be removed in 4.0
 */
final class SimpleBuilder extends TreeBuilder
{
    public function __construct(iterable $reducers)
    {
        trigger_deprecation('phplrt/parser', '3.4', <<<'MSG'
            Using "%s::class" is deprecated, please use "%s::class" instead.
            MSG, self::class, TreeBuilder::class);

        parent::__construct($reducers);
    }
}
