<?php

declare(strict_types=1);

namespace Phplrt\Source;

/**
 * @deprecated since phplrt 3.4 and will be removed in 4.0.
 */
interface MemoizableInterface
{
    public function refresh(): void;
}
