<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer\Value;

use Phplrt\Compiler\Printer\PrintableValueInterface;
use Phplrt\Compiler\Printer\PrinterInterface;

class LanguageInjection implements PrintableValueInterface
{
    /**
     * @readonly
     *
     * @psalm-readonly-allow-private-mutation
     */
    protected string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function print(PrinterInterface $printer): string
    {
        return \trim($this->code);
    }

    /**
     * @param non-empty-string $value
     */
    protected function contains(string $value): bool
    {
        $pattern = \sprintf('/%s\b/isum', \preg_quote($value, '/'));

        return (bool) \preg_match($pattern, $this->code);
    }
}
