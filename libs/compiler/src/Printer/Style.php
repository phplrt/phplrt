<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer;

class Style
{
    /**
     * @readonly
     *
     * @psalm-readonly-allow-private-mutation
     */
    public string $lineDelimiter;

    /**
     * @readonly
     *
     * @psalm-readonly-allow-private-mutation
     */
    public string $indentation;

    public function __construct(
        string $lineDelimiter = "\n",
        string $indentation = '    '
    ) {
        $this->indentation = $indentation;
        $this->lineDelimiter = $lineDelimiter;
    }

    /**
     * @return list<string>
     */
    private function lines(string $payload): iterable
    {
        $payload = \str_replace(["\r\n", "\n\r"], "\n", $payload);

        return \explode("\n", $payload);
    }

    public function indent(string $payload): string
    {
        $result = [];

        foreach ($this->lines($payload) as $line) {
            if (\trim($line) === '') {
                $result[] = '';
                continue;
            }

            $result[] = $this->indentation . $line;
        }

        return \implode($this->lineDelimiter, $result);
    }
}
