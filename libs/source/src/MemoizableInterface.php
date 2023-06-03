<?php

declare(strict_types=1);

namespace Phplrt\Source;

interface MemoizableInterface
{
    /**
     * @return void
     */
    public function refresh(): void;
}
