<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer;

final class PhpPrinter extends Printer implements PrinterInterface
{
    public function print(mixed $data, bool $multiline = true): string
    {
        return match (true) {
            $data === null => 'null',
            \is_scalar($data) => $this->scalar($data),
            \is_array($data) => $this->array($data, $multiline),
            $data instanceof PrintableValueInterface => $data->print($this),
            default => throw new \InvalidArgumentException(\sprintf(
                'Could not print value of type %s',
                \get_debug_type($data),
            )),
        };
    }

    private function listValues(array $data, bool $multiline): string
    {
        $result = [];

        foreach ($data as $value) {
            $line = \vsprintf('%s,', [
                $this->print($value),
            ]);

            $result[] = $multiline
                ? $this->style->indent($line)
                : $line;
        }

        if ($multiline) {
            return \implode($this->style->lineDelimiter, $result);
        }

        return \implode(' ', $result);
    }

    private function arrayValues(array $data, bool $multiline): string
    {
        $result = [];

        foreach ($data as $key => $value) {
            $line = \vsprintf('%s => %s,', [
                $this->print($key),
                $this->print($value),
            ]);

            $result[] = $multiline
                ? $this->style->indent($line)
                : $line;
        }

        if ($multiline) {
            return \implode($this->style->lineDelimiter, $result);
        }

        return \implode(' ', $result);
    }

    /**
     * @return non-empty-string
     */
    private function array(array $data, bool $multiline): string
    {
        if ($data === []) {
            return '[]';
        }

        $values = $this->arrayIsList($data)
            ? $this->listValues($data, $multiline)
            : $this->arrayValues($data, $multiline);

        if ($multiline) {
            /** @var non-empty-string */
            return \implode($this->style->lineDelimiter, ['[', $values, ']']);
        }

        return '[' . \rtrim($values, " \n\r\t\v\0,") . ']';
    }

    private function arrayIsList(array $array): bool
    {
        if (\function_exists('\\array_is_list')) {
            return \array_is_list($array);
        }

        if ($array === [] || \array_values($array) === $array) {
            return true;
        }

        $nextKey = -1;

        foreach ($array as $key => $_) {
            if ($key !== ++$nextKey) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param scalar $scalar
     *
     * @return non-empty-string
     */
    private function scalar(mixed $scalar): string
    {
        /** @var non-empty-string */
        return \var_export($scalar, true);
    }
}
