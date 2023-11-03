<?php

declare(strict_types=1);

namespace Phplrt\Source;

interface MemoizableInterface
{
    public function refresh(): void;
}
