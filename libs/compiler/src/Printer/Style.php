<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer;

class Style
{
    public function __construct(
        /**
         * @readonly
         *
         * @psalm-readonly-allow-private-mutation
         */
        public string $lineDelimiter = "\n",
        /**
         * @readonly
         *
         * @psalm-readonly-allow-private-mutation
         */
        public string $indentation = '    '
    ) {}

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
