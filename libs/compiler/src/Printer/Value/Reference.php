<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer\Value;

use Phplrt\Compiler\Printer\PrintableValueInterface;

abstract class Reference implements PrintableValueInterface
{
    /**
     * @param non-empty-string $reference
     * @param non-empty-string|null $alias
     */
    public function __construct(
        protected string $reference,
        protected ?string $alias = null,
    ) {}
}
