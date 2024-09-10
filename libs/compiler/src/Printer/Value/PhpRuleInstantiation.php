<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer\Value;

use Phplrt\Compiler\Printer\PrinterInterface;
use Phplrt\Parser\Grammar\RuleInterface;

final class PhpRuleInstantiation extends RuleInstantiation
{
    /**
     * @var class-string<RuleInterface>
     */
    private string $class;

    public function __construct(RuleInterface $rule)
    {
        $this->class = $rule::class;

        parent::__construct($rule);
    }

    public function print(PrinterInterface $printer): string
    {
        $arguments = \implode(', ', $this->printEachArgument(
            $printer,
            $this->getArguments(),
        ));

        return \vsprintf('new \\%s(%s)', [
            $this->class,
            $arguments,
        ]);
    }

    private function printEachArgument(PrinterInterface $printer, array $arguments): array
    {
        $result = [];

        foreach ($arguments as $argument) {
            $result[] = $printer->print($argument, false);
        }

        return $result;
    }
}
