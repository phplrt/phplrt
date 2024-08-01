<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer\Value;

use Phplrt\Compiler\Printer\PrintableValueInterface;

abstract class Reference implements PrintableValueInterface
{
    /**
     * @readonly
     *
     * @psalm-readonly-allow-private-mutation
     */
    protected string $reference;

    /**
     * @readonly
     *
     * @psalm-readonly-allow-private-mutation
     */
    protected ?string $alias = null;

    /**
     * @param non-empty-string $reference
     * @param non-empty-string|null $alias
     */
    public function __construct(string $reference, ?string $alias = null)
    {
        $this->alias = $alias;
        $this->reference = $reference;
    }
}
